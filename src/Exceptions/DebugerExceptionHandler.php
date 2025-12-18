<?php

namespace Meita\Debuger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DebugerExceptionHandler extends ExceptionHandler
{
    /**
     * @var array<int, string>
     */
    protected array $referenceMap = [];

    public function report(Throwable $e): void
    {
        if ($this->shouldEmail($e)) {
            $this->sendEmailReport($e);
        }

        parent::report($e);
    }

    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        if (!config('debuger.enabled', true)) {
            return $response;
        }

        $status = $response->getStatusCode();

        // Let Laravel keep its normal handling for non-5xx responses (e.g. auth/validation).
        if ($status < 500) {
            return $response;
        }

        $reference = $this->reference($e);

        if ($this->shouldReturnJson($request, $e)) {
            return response()->json([
                'message' => config('debuger.user_message'),
                'reference' => $reference,
            ], $status);
        }

        return response()->view('debuger::error', [
            'message' => config('debuger.user_message'),
            'reference' => $reference,
        ], $status);
    }

    protected function shouldEmail(Throwable $e): bool
    {
        if (!config('debuger.enabled', true)) {
            return false;
        }

        if (app()->runningUnitTests()) {
            return false;
        }

        if (app()->runningInConsole() && !config('debuger.mail.send_in_console', true)) {
            return false;
        }

        if (empty($this->mailTo())) {
            return false;
        }

        if ($this->isAuthOrValidation($e)) {
            return false;
        }

        if (!$this->shouldReport($e)) {
            return false;
        }

        return $this->statusFromException($e) >= 500;
    }

    protected function sendEmailReport(Throwable $e): void
    {
        $reference = $this->reference($e);

        try {
            $context = $this->requestContext();
            $to = $this->mailTo();

            if (empty($to)) {
                return;
            }

            Mail::send('debuger::email', [
                'exception' => $e,
                'context' => $context,
                'reference' => $reference,
            ], function ($message) use ($reference, $to) {
                $message->to($to);

                if ($from = config('debuger.mail.from')) {
                    $message->from($from);
                }

                $subject = config('debuger.mail.subject', 'Application Exception');
                $message->subject($subject . ' [' . $reference . ']');
            });
        } catch (Throwable $mailError) {
            Log::warning('Debuger failed to send exception email', [
                'reference' => $reference,
                'to' => config('debuger.mail.to'),
                'error' => $mailError->getMessage(),
                'exception' => $mailError,
            ]);
        }
    }

    /**
     * Normalize `debuger.mail.to` into the formats supported by `$message->to()`.
     *
     * Supports:
     * - a single string address
     * - comma/semicolon separated string addresses
     * - an array of addresses (indexed or associative address => name)
     */
    protected function mailTo(): array
    {
        $to = config('debuger.mail.to');

        if ($to instanceof Collection) {
            $to = $to->all();
        }

        if (is_string($to)) {
            $to = trim($to);

            if ($to === '') {
                return [];
            }

            if (str_starts_with($to, '[') && str_ends_with($to, ']')) {
                $decoded = json_decode($to, true);

                if (is_array($decoded)) {
                    $to = $decoded;
                }
            }
        }

        if (is_string($to)) {
            $parts = preg_split('/[;,]+/', $to) ?: [];

            return array_values(
                array_filter(
                    array_map('trim', $parts),
                    static fn ($value) => $value !== ''
                )
            );
        }

        if (!is_array($to)) {
            return [];
        }

        $keys = array_keys($to);
        $isAssociative = $keys !== range(0, count($to) - 1);

        if ($isAssociative) {
            $normalized = [];

            foreach ($to as $address => $name) {
                if (!is_string($address)) {
                    continue;
                }

                $address = trim($address);

                if ($address === '') {
                    continue;
                }

                if (is_string($name)) {
                    $name = trim($name);
                    $name = $name === '' ? null : $name;
                }

                $normalized[$address] = $name;
            }

            return $normalized;
        }

        $normalized = [];

        foreach ($to as $item) {
            if ($item instanceof Collection) {
                $item = $item->all();
            }

            if (is_string($item)) {
                $item = trim($item);

                if ($item === '') {
                    continue;
                }

                if (str_contains($item, ',') || str_contains($item, ';')) {
                    foreach (preg_split('/[;,]+/', $item) ?: [] as $part) {
                        $part = trim($part);

                        if ($part !== '') {
                            $normalized[] = $part;
                        }
                    }
                } else {
                    $normalized[] = $item;
                }

                continue;
            }

            if (is_array($item) && isset($item['address']) && is_string($item['address'])) {
                $address = trim($item['address']);

                if ($address === '') {
                    continue;
                }

                $name = null;

                if (isset($item['name']) && is_string($item['name'])) {
                    $name = trim($item['name']);
                    $name = $name === '' ? null : $name;
                }

                if ($name === null) {
                    $normalized[] = $address;
                } else {
                    $normalized[$address] = $name;
                }
            }
        }

        return $normalized;
    }

    protected function requestContext(): array
    {
        if (!app()->bound('request')) {
            return [];
        }

        $request = app('request');

        return [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => optional($request->user())->getAuthIdentifier(),
            'input' => $this->filteredInput($request->all()),
            'headers' => $request->headers->all(),
        ];
    }

    protected function filteredInput(array $input): array
    {
        $hidden = config('debuger.hidden_fields', []);

        return Arr::except($input, $hidden);
    }

    protected function reference(Throwable $e): string
    {
        $key = spl_object_id($e);

        if (!isset($this->referenceMap[$key])) {
            $hash = hash('sha256', $e->getMessage() . $e->getFile() . $e->getLine() . microtime(true));
            $this->referenceMap[$key] = config('debuger.reference_prefix', 'ERR-') . substr($hash, 0, 12);
        }

        return $this->referenceMap[$key];
    }

    protected function statusFromException(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        if (method_exists($e, 'getStatusCode')) {
            return (int) $e->getStatusCode();
        }

        if (property_exists($e, 'status')) {
            return (int) $e->status;
        }

        return 500;
    }

    protected function isAuthOrValidation(Throwable $e): bool
    {
        return $e instanceof AuthenticationException
            || $e instanceof AuthorizationException
            || $e instanceof ValidationException;
    }
}

<?php

namespace Meita\Debuger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
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

        if (app()->runningInConsole() || app()->runningUnitTests()) {
            return false;
        }

        if (!config('debuger.mail.to')) {
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
        try {
            $reference = $this->reference($e);
            $context = $this->requestContext();

            Mail::send('debuger::email', [
                'exception' => $e,
                'context' => $context,
                'reference' => $reference,
            ], function ($message) use ($reference) {
                $message->to(config('debuger.mail.to'));

                if ($from = config('debuger.mail.from')) {
                    $message->from($from);
                }

                $subject = config('debuger.mail.subject', 'Application Exception');
                $message->subject($subject . ' [' . $reference . ']');
            });
        } catch (Throwable $mailError) {
            Log::warning('Debuger failed to send exception email', [
                'error' => $mailError->getMessage(),
            ]);
        }
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

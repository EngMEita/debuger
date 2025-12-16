<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exception Report</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; color: #111; }
        pre { background: #f4f4f5; padding: 12px; border-radius: 8px; overflow-x: auto; }
        code { font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace; }
        .meta { margin: 0 0 12px 0; }
    </style>
</head>
<body>
    <h2>Exception captured</h2>
    <p class="meta"><strong>Reference:</strong> {{ $reference ?? 'n/a' }}</p>
    <p class="meta"><strong>Message:</strong> {{ $exception->getMessage() }}</p>
    <p class="meta"><strong>Location:</strong> {{ $exception->getFile() }}:{{ $exception->getLine() }}</p>

    @if(!empty($context))
        <h3>Request context</h3>
        @if(!empty($context['url']))
            <p class="meta"><strong>URL:</strong> {{ $context['url'] }}</p>
        @endif
        @if(!empty($context['method']))
            <p class="meta"><strong>Method:</strong> {{ $context['method'] }}</p>
        @endif
        @if(!empty($context['ip']))
            <p class="meta"><strong>IP:</strong> {{ $context['ip'] }}</p>
        @endif
        @if(!empty($context['user_id']))
            <p class="meta"><strong>User:</strong> {{ $context['user_id'] }}</p>
        @endif
        @if(!empty($context['input']))
            <p class="meta"><strong>Input:</strong></p>
            <pre><code>{{ json_encode($context['input'], JSON_PRETTY_PRINT) }}</code></pre>
        @endif
        @if(!empty($context['headers']))
            <p class="meta"><strong>Headers:</strong></p>
            <pre><code>{{ json_encode($context['headers'], JSON_PRETTY_PRINT) }}</code></pre>
        @endif
    @endif

    <h3>Stack trace</h3>
    <pre><code>{{ $exception }}</code></pre>
</body>
</html>

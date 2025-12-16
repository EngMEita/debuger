<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something went wrong</title>
    <style>
        :root {
            --bg: #f6f7fb;
            --panel: #ffffff;
            --text: #111827;
            --muted: #4b5563;
            --accent: #2563eb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg), #e5e7eb);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            place-items: center;
        }

        .card {
            max-width: 560px;
            width: 92%;
            background: var(--panel);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 12px 0;
            font-size: 26px;
            letter-spacing: -0.5px;
        }

        p {
            margin: 0 0 12px 0;
            line-height: 1.6;
            color: var(--muted);
        }

        .reference {
            margin-top: 14px;
            display: inline-block;
            padding: 10px 12px;
            background: #eef2ff;
            color: var(--accent);
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Oops! Something went wrong.</h1>
        <p>{{ $message ?? 'Please try again or contact support if the issue persists.' }}</p>
        @isset($reference)
            <div class="reference">Reference: {{ $reference }}</div>
        @endisset
    </main>
</body>
</html>

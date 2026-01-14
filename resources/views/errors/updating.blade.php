<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Updating - {{ config('app.name', 'TallCMS') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .container {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .spinner {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        .spinner::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 4px solid #e5e7eb;
            border-top-color: #3b82f6;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            color: #3b82f6;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        .status {
            background: #f3f4f6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .status-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }

        .status-value {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
        }

        .notice {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.5;
        }

        .notice strong {
            color: #374151;
        }

        .refresh-note {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .logo {
            margin-bottom: 1.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: #1e3a5f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">{{ config('app.name', 'TallCMS') }}</div>

        <div class="spinner">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </div>

        <h1>Updating System</h1>
        <p class="subtitle">Please wait while we apply the latest updates.</p>

        @if($version ?? false)
            <div class="status">
                <div class="status-label">Installing version</div>
                <div class="status-value">v{{ $version }}</div>
            </div>
        @endif

        <p class="notice">
            This process typically takes <strong>1-2 minutes</strong>. The page will automatically refresh when the update is complete.
        </p>

        <p class="refresh-note">
            This page auto-refreshes every 30 seconds
        </p>
    </div>
</body>
</html>

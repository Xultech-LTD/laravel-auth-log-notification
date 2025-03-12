{{-- Custom HTML/CSS Email Layout --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Login Alert</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f7f7f7;
            color: #333;
            padding: 30px;
        }
        .email-wrapper {
            background-color: #ffffff;
            padding: 30px;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .header {
            font-size: 20px;
            font-weight: bold;
            color: #1a202c;
        }
        .info {
            margin-top: 15px;
            font-size: 15px;
            line-height: 1.7;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="header">New Login Detected</div>
    <div class="info">
        Hello {{ $user->name ?? 'there' }},<br><br>
        A new login to your account was detected with the following details:
        <ul>
            <li><strong>IP Address:</strong> {{ $log->ip_address }}</li>
            <li><strong>Location:</strong> {{ $log->formatted_location }}</li>
            <li><strong>Device:</strong> {{ $log->device_summary }}</li>
            <li><strong>Time:</strong> {{ $log->login_at_formatted }}</li>
        </ul>
        If this was not you, please change your password immediately and contact support.
    </div>
    <div class="footer">
        — {{ config('app.name') }}
    </div>
</div>
</body>
</html>

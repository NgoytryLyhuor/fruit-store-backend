<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(to right, #111827, #6b7280);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .content {
            padding: 30px 0;
        }

        .button {
            display: inline-block;
            background-color: #111827;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin: 20px 0;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #1f2937;
        }

        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        .warning {
            background-color: #fef3cd;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }

        .warning-title {
            font-weight: 600;
            color: #92400e;
        }

        .warning-text {
            color: #92400e;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">Pure Flave</div>
    </div>

    <div class="content">
        <h2>Reset Your Password</h2>

        <p>Hello {{ $user->name ?? 'there' }},</p>

        <p>We received a request to reset your password for your Pure Flave account. If you made this request, click the
            button below to set a new password:</p>

        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        </div>

        <p>If the button doesn't work, you can also copy and paste the following link into your browser:</p>
        <p style="word-break: break-all; color: #6b7280; font-size: 14px;">{{ $resetUrl }}</p>

        <div class="warning">
            <div class="warning-title">Important Security Information:</div>
            <div class="warning-text">
                • This link will expire in 24 hours<br>
                • This link can only be used once<br>
                • If you didn't request this reset, please ignore this email
            </div>
        </div>

        <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain
            unchanged.</p>

        <p>If you have any questions or need help, contact our support team at support@pureflave.com</p>

        <p>Best regards,<br>
            The Pure Flave Team</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Pure Flave. All rights reserved.</p>
        <p>This email was sent to {{ $user->email }}</p>
    </div>
</body>

</html>

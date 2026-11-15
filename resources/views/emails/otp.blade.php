<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Security Verification Code' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 40px 15px;
            box-sizing: border-box;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #0b132b;
            padding: 30px;
            text-align: center;
        }
        .brand-crest {
            display: inline-block;
            width: 44px;
            height: 44px;
            line-height: 44px;
            background: #f59e0b;
            color: #0b132b;
            font-weight: 800;
            font-size: 20px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .brand-title {
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin: 0;
        }
        .brand-subtitle {
            color: #f59e0b;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            margin-top: 4px;
        }
        .content {
            padding: 35px 30px;
        }
        h2 {
            color: #0f172a;
            font-size: 22px;
            margin-top: 0;
            margin-bottom: 12px;
            font-weight: 700;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin: 0 0 18px 0;
        }
        .otp-box {
            background-color: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 24px 15px;
            text-align: center;
            margin: 25px 0;
        }
        .otp-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .otp-code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #0b132b;
            margin: 0;
        }
        .otp-expiry {
            font-size: 12px;
            color: #d97706;
            font-weight: 600;
            margin-top: 8px;
        }
        .security-notice {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 13px;
            color: #92400e;
            margin: 20px 0 10px 0;
            line-height: 1.5;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer p {
            margin: 4px 0;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <img src="{{ asset('assets/images/logo.svg') }}" alt="HeadWorld Facility Management" style="height: 44px; max-width: 240px; margin-bottom: 6px;">
                <div class="brand-subtitle">CAMPUS OPERATIONS</div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <h2>{{ $heading ?? 'One-Time Verification Code' }}</h2>
                <p>Hello <strong>{{ $user->first_name }}</strong>,</p>
                <p>{{ $introMessage ?? 'Use the one-time password (OTP) below to authenticate your request on the Headworld Campus Operations portal.' }}</p>

                <!-- OTP Display Box -->
                <div class="otp-box">
                    <div class="otp-label">Your 6-Digit Verification Code</div>
                    <div class="otp-code">{{ $code }}</div>
                    <div class="otp-expiry">&#9201; Expires in {{ $ttlMinutes ?? 10 }} minutes</div>
                </div>

                <div class="security-notice">
                    <strong>Security Warning:</strong> Never share this code with anyone, including university IT or facility maintenance personnel. If you did not initiate this security action, someone may be attempting to access your account. Please notify the operations desk immediately.
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; {{ date('Y') }} Headworld Campus Facility Management. All rights reserved.</p>
                <p>This is an automated system dispatch. Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>


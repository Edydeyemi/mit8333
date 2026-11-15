<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Completed</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; }
        .wrapper { width: 100%; background-color: #f1f5f9; padding: 40px 15px; box-sizing: border-box; }
        .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0; }
        .header { background-color: #0b132b; padding: 30px; text-align: center; }
        .brand-crest { display: inline-block; width: 44px; height: 44px; line-height: 44px; background: #10b981; color: #ffffff; font-weight: 800; font-size: 20px; border-radius: 10px; margin-bottom: 10px; }
        .brand-title { color: #ffffff; font-size: 18px; font-weight: 700; letter-spacing: 1.5px; margin: 0; }
        .brand-subtitle { color: #10b981; font-size: 11px; font-weight: 600; letter-spacing: 2px; margin-top: 4px; }
        .content { padding: 35px 30px; }
        .info-card { background: #f0fdf4; border-radius: 8px; padding: 16px; margin: 20px 0; border: 1px solid #bbf7d0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .info-label { color: #166534; font-weight: 600; }
        .info-value { color: #14532d; font-weight: 600; }
        .cta-btn { display: inline-block; background-color: #0b132b; color: #f59e0b !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; margin-top: 20px; text-align: center; }
        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="{{ asset('assets/images/logo.svg') }}" alt="HeadWorld Facility Management" style="height: 44px; max-width: 240px; margin-bottom: 6px;">
                <div class="brand-subtitle">WORK ORDER SIGN-OFF</div>
            </div>

            <div class="content">
                <h2 style="color: #0f172a; margin-top: 0; font-size: 20px;">
                    Work Order Successfully Resolved
                </h2>

                <p style="font-size: 15px; line-height: 1.6; color: #475569;">
                    Hello {{ $recipient->name }}, our maintenance technician has completed all repairs and marked ticket <strong>{{ $serviceRequest->tracking_code }}</strong> as resolved.
                </p>

                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Tracking Code:</span>
                        <span class="info-value" style="font-family: monospace;">{{ $serviceRequest->tracking_code }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Title:</span>
                        <span class="info-value">{{ $serviceRequest->title }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Resolved At:</span>
                        <span class="info-value">{{ now()->format('M d, Y H:i') }}</span>
                    </div>
                    @if(!empty($resolutionNotes))
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #86efac;">
                            <div class="info-label" style="margin-bottom: 4px;">Resolution Summary:</div>
                            <div style="font-size: 13px; color: #166534; font-style: italic;">"{{ $resolutionNotes }}"</div>
                        </div>
                    @endif
                </div>

                <div style="text-align: center;">
                    <a href="{{ url('/portal/requests/' . $serviceRequest->slug) }}" class="cta-btn">
                        View Completion Proof & Submit Feedback &rarr;
                    </a>
                </div>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} Headworld University Campus Operations &bull; Quality Assurance Team
            </div>
        </div>
    </div>
</body>
</html>


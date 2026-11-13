<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Assigned</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; }
        .wrapper { width: 100%; background-color: #f1f5f9; padding: 40px 15px; box-sizing: border-box; }
        .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0; }
        .header { background-color: #0b132b; padding: 30px; text-align: center; }
        .brand-crest { display: inline-block; width: 44px; height: 44px; line-height: 44px; background: #f59e0b; color: #0b132b; font-weight: 800; font-size: 20px; border-radius: 10px; margin-bottom: 10px; }
        .brand-title { color: #ffffff; font-size: 18px; font-weight: 700; letter-spacing: 1.5px; margin: 0; }
        .brand-subtitle { color: #f59e0b; font-size: 11px; font-weight: 600; letter-spacing: 2px; margin-top: 4px; }
        .content { padding: 35px 30px; }
        .info-card { background: #f8fafc; border-radius: 8px; padding: 16px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .info-label { color: #64748b; font-weight: 600; }
        .info-value { color: #0f172a; font-weight: 600; }
        .cta-btn { display: inline-block; background-color: #0b132b; color: #f59e0b !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; margin-top: 20px; text-align: center; }
        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="brand-crest">V</div>
                <div class="brand-title">HEADWORLD OPERATIONS</div>
                <div class="brand-subtitle">FIELD MAINTENANCE ALLOCATION</div>
            </div>

            <div class="content">
                <h2 style="color: #0f172a; margin-top: 0; font-size: 20px;">
                    @if($isOfficerRecipient)
                        New Task Assigned: Work Order {{ $serviceRequest->tracking_code }}
                    @else
                        Technician Dispatched to Your Request
                    @endif
                </h2>

                <p style="font-size: 15px; line-height: 1.6; color: #475569;">
                    @if($isOfficerRecipient)
                        Hello {{ $recipient->name }}, you have been allocated to resolve maintenance ticket <strong>{{ $serviceRequest->tracking_code }}</strong>. Please review fault details below and update your status upon site arrival.
                    @else
                        Hello {{ $recipient->name }}, maintenance technician <strong>{{ $officer->name }}</strong> has been allocated to inspect and resolve your service request.
                    @endif
                </p>

                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Tracking Code:</span>
                        <span class="info-value" style="font-family: monospace;">{{ $serviceRequest->tracking_code }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Assigned Technician:</span>
                        <span class="info-value">{{ $officer->name }} ({{ $officer->email }})</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Facility Location:</span>
                        <span class="info-value">{{ $serviceRequest->building }}, {{ $serviceRequest->wing_floor }} &bull; {{ $serviceRequest->room_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Urgency:</span>
                        <span class="info-value" style="color: #b45309; text-transform: uppercase;">{{ $serviceRequest->priority }}</span>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="{{ $isOfficerRecipient ? url('/facility/tasks/' . $serviceRequest->slug) : url('/portal/requests/' . $serviceRequest->slug) }}" class="cta-btn">
                        Open Work Order Details &rarr;
                    </a>
                </div>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} Headworld University Campus Operations &bull; Task Allocation Service
            </div>
        </div>
    </div>
</body>
</html>


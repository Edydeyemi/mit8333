<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Request Lodged</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; }
        .wrapper { width: 100%; background-color: #f1f5f9; padding: 40px 15px; box-sizing: border-box; }
        .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0; }
        .header { background-color: #0b132b; padding: 30px; text-align: center; }
        .brand-crest { display: inline-block; width: 44px; height: 44px; line-height: 44px; background: #f59e0b; color: #0b132b; font-weight: 800; font-size: 20px; border-radius: 10px; margin-bottom: 10px; }
        .brand-title { color: #ffffff; font-size: 18px; font-weight: 700; letter-spacing: 1.5px; margin: 0; }
        .brand-subtitle { color: #f59e0b; font-size: 11px; font-weight: 600; letter-spacing: 2px; margin-top: 4px; }
        .content { padding: 35px 30px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge-emergency { background: #fee2e2; color: #991b1b; }
        .badge-high { background: #fef3c7; color: #92400e; }
        .badge-medium { background: #e0f2fe; color: #075985; }
        .badge-low { background: #f1f5f9; color: #475569; }
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
                <img src="{{ asset('assets/images/logo.svg') }}" alt="HeadWorld Facility Management" style="height: 44px; max-width: 240px; margin-bottom: 6px;">
                <div class="brand-subtitle">CAMPUS FACILITY MANAGEMENT</div>
            </div>

            <div class="content">
                <h2 style="color: #0f172a; margin-top: 0; font-size: 20px;">
                    @if($isDispatcher)
                        Urgent Dispatch Notice: New Incident Lodged
                    @else
                        Service Request Confirmed
                    @endif
                </h2>

                <p style="font-size: 15px; line-height: 1.6; color: #475569;">
                    @if($isDispatcher)
                        A new campus facility complaint has been logged and requires operational triage and technician allocation.
                    @else
                        Hello {{ $recipient->name }}, your campus maintenance request has been recorded in the central facility ledger.
                    @endif
                </p>

                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Tracking Reference:</span>
                        <span class="info-value" style="font-family: monospace;">{{ $serviceRequest->tracking_code }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Complaint Title:</span>
                        <span class="info-value">{{ $serviceRequest->title }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Category:</span>
                        <span class="info-value">{{ $serviceRequest->category?->name ?? 'Facility Maintenance' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location:</span>
                        <span class="info-value">{{ $serviceRequest->building }}, {{ $serviceRequest->wing_floor }} ({{ $serviceRequest->room_number }})</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Urgency Priority:</span>
                        <span class="badge badge-{{ $serviceRequest->priority }}">{{ strtoupper($serviceRequest->priority) }}</span>
                    </div>
                </div>

                <div style="text-align: center;">
                    <a href="{{ $isDispatcher ? url('/admin/requests/' . $serviceRequest->slug) : url('/portal/requests/' . $serviceRequest->slug) }}" class="cta-btn">
                        View Request in Portal &rarr;
                    </a>
                </div>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} Headworld University Campus Operations &bull; Automated Dispatch System
            </div>
        </div>
    </div>
</body>
</html>


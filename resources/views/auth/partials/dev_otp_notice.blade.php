@props([
    'otp' => null,
    'targetInputId' => null,
])

@if (!empty($otp))
    <div class="alert alert-warning border border-warning shadow-sm rounded-3 p-3 mb-4 dev-otp-alert" role="alert">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom border-warning border-opacity-25">
            <span class="badge bg-dark text-warning font-mono text-uppercase d-inline-flex align-items-center px-2 py-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                <i class="bi bi-cone-striped me-1"></i> {{ strtoupper(app()->environment()) }} ENVIRONMENT
            </span>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted fw-semibold" style="font-size: 0.78rem;">Generated OTP:</span>
                <span class="badge bg-warning text-navy-900 font-mono fs-6 fw-bold letter-spacing-1 px-2 py-1 shadow-xs border border-dark border-opacity-10 dev-otp-code" id="devOtpBadge">
                    {{ $otp }}
                </span>
                @if ($targetInputId)
                    <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2 fw-semibold" style="font-size: 0.75rem; height: 26px;"
                        onclick="autoFillDevOtp('{{ $otp }}', '{{ $targetInputId }}')"
                        title="Click to paste into code input">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Auto-fill
                    </button>
                @endif
            </div>
        </div>

        <div class="small text-dark mb-2" style="font-size: 0.82rem; line-height: 1.45;">
            <i class="bi bi-info-circle-fill text-warning me-1"></i>
            <strong>Notice:</strong> This verification code is displayed on-screen because the application is running in the
            <span class="badge bg-light text-dark border font-mono">{{ app()->environment() }}</span> environment where external email delivery may be disabled or unrouted.
        </div>

        <div class="small text-muted border-top border-warning border-opacity-25 pt-2 mt-1 d-flex align-items-center justify-content-between" style="font-size: 0.75rem;">
            <span>
                <i class="bi bi-gear-fill me-1 text-secondary"></i>
                To turn off this display, configure <code class="text-danger fw-bold">SHOW_DEV_OTP=false</code> in your <code>.env</code> file.
            </span>
        </div>
    </div>

    @if ($targetInputId)
        <script>
            function autoFillDevOtp(code, targetId) {
                const targetInput = document.getElementById(targetId);
                if (targetInput) {
                    targetInput.value = code;
                    targetInput.focus();
                    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    targetInput.dispatchEvent(new Event('change', { bubbles: true }));

                    if (typeof showToast === 'function') {
                        showToast('Verification code auto-filled!', 'info');
                    }
                }
            }
        </script>
    @endif
@endif

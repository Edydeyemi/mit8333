<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Two-Factor Authentication (2FA) verification for Headworld Campus Operations.">
    <title>Two-Factor Authentication | Headworld Campus Operations</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <!-- Bootstrap 5 CSS Foundation -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Design Tokens & Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
</head>

<body>

    <div class="auth-split-wrapper">
        <!-- Form Side -->
        <div class="auth-form-side">
            <div class="mb-4">
                <a href="{{ url('/') }}"
                    class="d-inline-flex align-items-center gap-2 text-decoration-none text-navy-900 mb-3"
                    aria-label="HeadWorld Campus Operations">
                    <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="HeadWorld Facility Management" class="brand-logo-img" style="height: 46px; max-width: 270px;">
                </a>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark fw-bold px-2 py-1 small text-uppercase">
                        <i class="bi bi-shield-lock-fill me-1"></i> Two-Factor Authentication
                    </span>
                </div>
                <h2 class="fw-bold text-navy-900">Security Verification</h2>
                <p class="text-muted small">
                    A secure 6-digit verification code has been dispatched to
                    <strong class="text-navy-900">{{ $maskedEmail }}</strong>. Enter it below to complete your sign in.
                </p>
            </div>

            <!-- Session Alerts -->
            @if (session('status'))
                <div class="alert alert-info alert-dismissible fade show border-0 rounded-3 small mb-3" role="alert">
                    <i class="bi bi-info-circle-fill me-1"></i> {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 small mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Verification Failed:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- 2FA OTP Form -->
            <form id="twoFactorForm" method="POST" action="{{ route('login.2fa.verify') }}">
                @csrf

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="otpCodeInput" class="form-label-headworld mb-0">6-Digit Verification Code</label>
                        <span id="countdownTimer" class="small text-muted fw-bold">
                            <i class="bi bi-clock me-1"></i> <span id="timerText">10:00</span>
                        </span>
                    </div>

                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-key-fill text-warning"></i>
                        </span>
                        <input type="text" name="code" id="otpCodeInput"
                            class="form-control form-control-headworld font-mono text-center fs-4 letter-spacing-2 border-start-0 @error('code') is-invalid @enderror"
                            maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"
                            placeholder="••••••" autofocus required>
                    </div>
                    @error('code')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    <div class="form-text small text-muted text-center mt-2">
                        Enter the numbers sent to your email address. Codes expire after 10 minutes.
                    </div>
                </div>

                <button type="submit" id="verifyBtn" class="btn btn-gold w-100 py-2 fs-6 shadow-sm mb-3">
                    <i class="bi bi-shield-check me-1"></i> Verify Code & Enter Portal
                </button>
            </form>

            <!-- Resend Section -->
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border bg-light mb-4">
                <div>
                    <div class="small fw-bold text-navy-900">Didn't receive the email?</div>
                    <div class="small text-muted">Check your spam or junk folder.</div>
                </div>
                <form method="POST" action="{{ route('login.2fa.resend') }}" id="resendForm">
                    @csrf
                    <button type="submit" id="resendBtn" class="btn btn-sm btn-outline-secondary"
                        @if ($cooldown > 0) disabled @endif>
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        <span id="resendText">
                            @if ($cooldown > 0)
                                Resend in {{ $cooldown }}s
                            @else
                                Resend Code
                            @endif
                        </span>
                    </button>
                </form>
            </div>

            <div class="pt-3 border-top text-center">
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link small text-muted text-decoration-none p-0">
                        <i class="bi bi-box-arrow-left me-1"></i> Cancel & Sign in as different user
                    </button>
                </form>
            </div>
        </div>

        <!-- Visual Side -->
        <div class="auth-visual-side">
            <div>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill text-uppercase">
                        <i class="bi bi-shield-lock-fill me-1"></i> Zero Trust Identity
                    </div>
                </div>
                <h2 class="display-6 fw-bold text-white mb-3">Campus Security & Integrity First</h2>
                <p class="text-white-50 max-w-500">
                    Two-factor authentication ensures that only verified students, faculty members, and dispatched technicians can log and modify campus infrastructure jobs.
                </p>
            </div>

            <div class="bg-dark bg-opacity-50 p-4 rounded-4 backdrop-blur border border-white border-opacity-10">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('assets/images/logo-mark.svg') }}" alt="HeadWorld Crest" class="brand-logo-mark" style="width: 48px; height: 48px;">
                    <div>
                        <div class="text-white fw-bold">Enterprise Campus Care</div>
                        <div class="small text-warning">ISO 27001 Identity Safeguard</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/toast.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <script>
        // 10-minute expiry countdown timer
        let totalSeconds = 600;
        const timerText = document.getElementById("timerText");

        const timerInterval = setInterval(() => {
            if (totalSeconds <= 0) {
                clearInterval(timerInterval);
                if (timerText) timerText.textContent = "Expired";
                return;
            }
            totalSeconds--;
            const mins = Math.floor(totalSeconds / 60);
            const secs = totalSeconds % 60;
            if (timerText) {
                timerText.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
        }, 1000);

        // Resend cooldown timer if cooldown > 0
        let cooldownSeconds = {{ $cooldown ?? 0 }};
        const resendBtn = document.getElementById("resendBtn");
        const resendText = document.getElementById("resendText");

        if (cooldownSeconds > 0) {
            const cooldownInterval = setInterval(() => {
                cooldownSeconds--;
                if (cooldownSeconds <= 0) {
                    clearInterval(cooldownInterval);
                    if (resendBtn) resendBtn.disabled = false;
                    if (resendText) resendText.textContent = "Resend Code";
                } else {
                    if (resendText) resendText.textContent = `Resend in ${cooldownSeconds}s`;
                }
            }, 1000);
        }

        // Auto submit when 6 digits entered
        const otpInput = document.getElementById("otpCodeInput");
        otpInput?.addEventListener("input", function() {
            // Strip any non-digits
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                const btn = document.getElementById("verifyBtn");
                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> Validating Security Token...`;
                document.getElementById("twoFactorForm").submit();
            }
        });

        document.getElementById("twoFactorForm")?.addEventListener("submit", () => {
            const btn = document.getElementById("verifyBtn");
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> Validating Security Token...`;
        });
    </script>
</body>

</html>


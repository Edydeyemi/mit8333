<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery | Headworld Campus Operations</title>

    <!-- Bootstrap 5 CSS Foundation -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="{{ asset('assets/font/bootstrap-icons.min.css') }}" rel="stylesheet">

    <!-- Custom Design Tokens & Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
</head>

<body>

    <div class="auth-split-wrapper">
        <div class="auth-form-side">
            <div class="mb-4">
                <a href="{{ url('/') }}"
                    class="d-inline-flex align-items-center gap-2 text-decoration-none text-navy-900 mb-3">
                    <div class="brand-crest">H</div>
                    <div>
                        <div class="brand-title text-dark">HEADWORLD</div>
                        <div class="brand-subtitle text-warning">CAMPUS OPERATIONS</div>
                    </div>
                </a>
                <h2 class="fw-bold text-navy-900 mt-1">Account Recovery</h2>
                <p class="text-muted small">
                    Recover your campus account using a verified 6-digit one-time token sent to your university email.
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
                    <strong>Action Required:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Step 1: Request Code Form -->
            <div id="step1Container" @if(session('status') || old('code') || $errors->has('code') || $errors->has('password')) style="display:none;" @endif>
                <form id="requestCodeForm" method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="recoveryEmail" class="form-label-headworld">Registered University Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" name="email" id="recoveryEmail"
                                class="form-control form-control-headworld border-start-0 @error('email') is-invalid @enderror"
                                placeholder="name@mit.edu" value="{{ old('email', 'student@mit.edu') }}" required autofocus>
                        </div>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" id="sendCodeBtn" class="btn btn-gold w-100 py-2 fs-6 shadow-sm mb-3">
                        <i class="bi bi-send me-1"></i> Send 6-Digit Recovery Code
                    </button>
                </form>

                <div class="text-center small text-muted">
                    Already have a verification code?
                    <button type="button" class="btn btn-link small text-navy-900 p-0 fw-bold text-decoration-underline"
                        onclick="showStep2()">
                        Enter Code & New Password
                    </button>
                </div>
            </div>

            <!-- Step 2: Enter Code & New Password -->
            <div id="step2Container" @if(!session('status') && !old('code') && !$errors->has('code') && !$errors->has('password')) style="display:none;" @endif>
                <div class="alert alert-warning border-0 rounded-3 small mb-3">
                    <i class="bi bi-shield-check me-1"></i>
                    Enter the 6-digit verification code sent to your email and your new password.
                </div>

                <form id="resetPasswordForm" method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="resetEmailInput" class="form-label-headworld">University Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" name="email" id="resetEmailInput"
                                class="form-control form-control-headworld border-start-0 @error('email') is-invalid @enderror"
                                placeholder="name@mit.edu" value="{{ old('email', session('reset_email', 'student@mit.edu')) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="otpCode" class="form-label-headworld">6-Digit Verification Token</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-key text-warning"></i>
                            </span>
                            <input type="text" name="code" id="otpCode"
                                class="form-control form-control-headworld font-mono text-center fs-5 letter-spacing-2 border-start-0 @error('code') is-invalid @enderror"
                                maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="839204"
                                value="{{ old('code') }}" required>
                        </div>
                        @error('code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="newPassword" class="form-label-headworld">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" name="password" id="newPassword"
                                class="form-control form-control-headworld border-start-0 border-end-0 @error('password') is-invalid @enderror"
                                placeholder="Min. 8 characters with numbers & mixed case" required>
                            <button class="btn border-start-0 bg-white text-muted" type="button"
                                id="toggleNewPwdBtn" aria-label="Toggle password visibility">
                                <i class="bi bi-eye" id="toggleNewPwdIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="form-text small text-muted">
                            Must contain at least 8 characters, with mixed uppercase and lowercase letters and at least one number.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="newPasswordConfirm" class="form-label-headworld">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input type="password" name="password_confirmation" id="newPasswordConfirm"
                                class="form-control form-control-headworld border-start-0 border-end-0" placeholder="Confirm new password" required>
                            <button class="btn border-start-0 bg-white text-muted" type="button"
                                id="toggleConfirmPwdBtn" aria-label="Toggle confirm password visibility">
                                <i class="bi bi-eye" id="toggleConfirmPwdIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="confirmResetBtn" class="btn btn-gold w-100 py-2 fs-6 shadow-sm mb-3">
                        <i class="bi bi-shield-check me-1"></i> Update Password & Sign In
                    </button>
                </form>

                <div class="text-center small text-muted">
                    <button type="button" class="btn btn-link small text-muted p-0 text-decoration-underline"
                        onclick="showStep1()">
                        &larr; Request a different email address
                    </button>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-center">
                <a href="{{ route('login') }}" class="small text-muted text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Remembered your password? Back to Sign In
                </a>
            </div>
        </div>

        <!-- Visual Side -->
        <div class="auth-visual-side">
            <div>
                <div class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill text-uppercase mb-3">
                    Institutional Identity & Protection
                </div>
                <h2 class="display-6 fw-bold text-white mb-3">Secure Account Recovery</h2>
                <p class="text-white-50">
                    Headworld credentials are encrypted and governed under institutional security standards. Password modifications require multi-factor identity authorization.
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/toast.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <script>
        function showStep2() {
            document.getElementById('step1Container').style.display = 'none';
            document.getElementById('step2Container').style.display = 'block';
            const email = document.getElementById('recoveryEmail').value.trim();
            if (email) {
                document.getElementById('resetEmailInput').value = email;
            }
        }

        function showStep1() {
            document.getElementById('step2Container').style.display = 'none';
            document.getElementById('step1Container').style.display = 'block';
        }

        // Toggle password visibility
        const toggleNewPwdBtn = document.getElementById("toggleNewPwdBtn");
        const newPwdInput = document.getElementById("newPassword");
        const newPwdIcon = document.getElementById("toggleNewPwdIcon");

        toggleNewPwdBtn?.addEventListener("click", () => {
            if (newPwdInput.type === "password") {
                newPwdInput.type = "text";
                newPwdIcon.className = "bi bi-eye-slash";
            } else {
                newPwdInput.type = "password";
                newPwdIcon.className = "bi bi-eye";
            }
        });

        // Toggle confirm password visibility
        const toggleConfirmPwdBtn = document.getElementById("toggleConfirmPwdBtn");
        const confirmPwdInput = document.getElementById("newPasswordConfirm");
        const confirmPwdIcon = document.getElementById("toggleConfirmPwdIcon");

        toggleConfirmPwdBtn?.addEventListener("click", () => {
            if (confirmPwdInput.type === "password") {
                confirmPwdInput.type = "text";
                confirmPwdIcon.className = "bi bi-eye-slash";
            } else {
                confirmPwdInput.type = "password";
                confirmPwdIcon.className = "bi bi-eye";
            }
        });

        document.getElementById("requestCodeForm")?.addEventListener("submit", () => {
            const btn = document.getElementById("sendCodeBtn");
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> Dispatching OTP Token...`;
        });

        document.getElementById("resetPasswordForm")?.addEventListener("submit", () => {
            const btn = document.getElementById("confirmResetBtn");
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> Updating Password...`;
        });
    </script>
</body>

</html>

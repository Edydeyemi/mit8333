<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Sign in to Headworld Campus Operations Portal for students, faculty, and maintenance engineers.">
    <title>Sign In | Headworld Campus Operations</title>

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

    <div id="globalPageLoader" class="page-loader-progress"></div>

    <div class="auth-split-wrapper">
        <!-- Form Side -->
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
                <h2 class="fw-bold text-navy-900 mt-2">Sign In to Headworld</h2>
                <p class="text-muted small">Access your maintenance requests, technician queue, or administrative
                    dashboard.</p>
            </div>

            <!-- Session Alerts -->
            @if (session('status'))
                <div class="alert alert-info alert-dismissible fade show border-0 rounded-3 small mb-3" role="alert">
                    <i class="bi bi-info-circle-fill me-1"></i> {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 small mb-3" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 small mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Authentication Error:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="mb-3">
                    <label for="emailInput" class="form-label-headworld">University Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" name="email" id="emailInput"
                            class="form-control form-control-headworld border-start-0 @error('email') is-invalid @enderror"
                            placeholder="name@unilag.edu" value="{{ old('email') }}" required autofocus>
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="passwordInput" class="form-label-headworld mb-0">Password</label>
                        <a href="{{ route('password.request') }}"
                            class="small text-muted text-decoration-underline">Forgot password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" name="password" id="passwordInput"
                            class="form-control form-control-headworld border-start-0 border-end-0 @error('password') is-invalid @enderror"
                            placeholder="••••••••" required>
                        <button class="btn border-start-0 bg-white text-muted" type="button" id="togglePasswordBtn"
                            aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" value="1" id="rememberMe"
                            checked>
                        <label class="form-check-label small text-muted" for="rememberMe">
                            Remember this device
                        </label>
                    </div>
                    <span class="badge bg-warning bg-opacity-25 text-dark small">
                        <i class="bi bi-shield-lock me-1"></i> 2FA Protected
                    </span>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-gold w-100 py-2 fs-6 shadow-sm mb-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Continue to 2FA Verification
                </button>

                <div class="text-center small text-muted">
                    New campus resident or staff member?
                    <a href="{{ route('register') }}" class="text-navy-900 fw-bold text-decoration-underline">Register
                        account</a>
                </div>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <a href="{{ url('/') }}" class="small text-muted text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Return to Campus Homepage
                </a>
            </div>
        </div>

        <!-- Visual Side -->
        <div class="auth-visual-side">
            <div>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill text-uppercase">
                        <i class="bi bi-shield-check me-1"></i> Multi-Factor Secured
                    </div>
                </div>
                <h2 class="display-6 fw-bold text-white mb-3">Headworld Facility Operations Protocol</h2>
                <p class="text-white-50 max-w-500">
                    Integrated service dispatch connecting Queen's Hall hostels, Maxwell lecture halls, and advanced
                    engineering labs to designated technical response squads.
                </p>
            </div>

            <div class="bg-dark bg-opacity-50 p-4 rounded-4 backdrop-blur border border-white border-opacity-10">
                <div class="d-flex align-items-center gap-3">
                    <div class="brand-crest" style="width:45px; height:45px; font-size:1.4rem;">H</div>
                    <div>
                        <div class="text-white fw-bold">Enterprise Campus Care</div>
                        <div class="small text-warning">2FA Institutional Identity Safeguard</div>
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
        // Toggle password visibility
        const toggleBtn = document.getElementById("togglePasswordBtn");
        const pwdInput = document.getElementById("passwordInput");
        const pwdIcon = document.getElementById("togglePasswordIcon");

        toggleBtn?.addEventListener("click", () => {
            if (pwdInput.type === "password") {
                pwdInput.type = "text";
                pwdIcon.className = "bi bi-eye-slash";
            } else {
                pwdInput.type = "password";
                pwdIcon.className = "bi bi-eye";
            }
        });

        // Form submission loading state
        document.getElementById("loginForm")?.addEventListener("submit", () => {
            const btn = document.getElementById("submitBtn");
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> Verifying credentials & dispatching OTP...`;
        });
    </script>
</body>

</html>
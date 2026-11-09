<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register your university profile on Headworld Campus Operations.">
    <title>Register Profile | Headworld Campus Operations</title>

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
        <div class="auth-form-side" style="max-width: 680px;">
            <div class="mb-4">
                <a href="{{ url('/') }}"
                    class="d-inline-flex align-items-center gap-2 text-decoration-none text-navy-900 mb-3">
                    <div class="brand-crest">H</div>
                    <div>
                        <div class="brand-title text-dark">HEADWORLD</div>
                        <div class="brand-subtitle text-warning">CAMPUS OPERATIONS</div>
                    </div>
                </a>
                <h2 class="fw-bold text-navy-900 mt-1">Register University Profile</h2>
                <p class="text-muted small">Register your student or staff profile to lodge maintenance requests and track on-campus repair dispatches.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 small mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Registration Error:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form id="registerForm" method="POST" action="{{ route('register.post') }}">
                @csrf

                <div class="row g-3">
                    <!-- First Name -->
                    <div class="col-md-6">
                        <label for="regFirstName" class="form-label-headworld">First Name</label>
                        <input type="text" name="first_name" id="regFirstName"
                            class="form-control-headworld @error('first_name') is-invalid @enderror"
                            placeholder="e.g. Julian" value="{{ old('first_name') }}" required>
                        @error('first_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-6">
                        <label for="regLastName" class="form-label-headworld">Last Name</label>
                        <input type="text" name="last_name" id="regLastName"
                            class="form-control-headworld @error('last_name') is-invalid @enderror"
                            placeholder="e.g. Hayes" value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- University Email -->
                    <div class="col-md-6">
                        <label for="regEmail" class="form-label-headworld">University Email</label>
                        <input type="email" name="email" id="regEmail"
                            class="form-control-headworld @error('email') is-invalid @enderror"
                            placeholder="e.g. j.hayes@mit.edu" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Contact Phone Number -->
                    <div class="col-md-6">
                        <label for="regPhone" class="form-label-headworld">Contact Phone (Optional)</label>
                        <input type="tel" name="mobile" id="regPhone"
                            class="form-control-headworld @error('mobile') is-invalid @enderror"
                            placeholder="+1 (555) 000-0000" value="{{ old('mobile') }}">
                        @error('mobile')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label for="regPassword" class="form-label-headworld">Password</label>
                        <input type="password" name="password" id="regPassword"
                            class="form-control-headworld @error('password') is-invalid @enderror"
                            placeholder="Min. 8 chars, mixed case & numbers" required>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6">
                        <label for="regConfirmPassword" class="form-label-headworld">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="regConfirmPassword"
                            class="form-control-headworld" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="form-check my-3">
                    <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                    <label class="form-check-label small text-muted" for="termsCheck">
                        I agree to abide by the university campus facility and property maintenance guidelines.
                    </label>
                </div>

                <button type="submit" id="submitRegBtn" class="btn btn-gold w-100 py-2 fs-6 shadow-sm mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i> Complete Registration & Enter
                </button>

                <div class="text-center small text-muted">
                    Already registered?
                    <a href="{{ route('login') }}" class="text-navy-900 fw-bold text-decoration-underline">Sign in here</a>
                </div>
            </form>
        </div>

        <!-- Visual Side -->
        <div class="auth-visual-side">
            <div>
                <div class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill text-uppercase mb-3">
                    University Community
                </div>
                <h2 class="display-6 fw-bold text-white mb-3">Rapid Campus Care for All Residents</h2>
                <p class="text-white-50">
                    From faulty lab power rails to broken hostel water pipes, your registered Headworld profile gives you a transparent window into active technicians, SLA timelines, and instant resolution feedback.
                </p>
            </div>

            <div class="bg-dark bg-opacity-50 p-4 rounded-4 backdrop-blur border border-white border-opacity-10">
                <div class="d-flex align-items-center justify-content-between text-white small">
                    <span>Active Campus Personnel</span>
                    <span class="text-warning fw-bold">6,840+ Registered</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/toast.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student & Staff Portal | Headworld Campus Operations</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
</head>
<body>
    <div class="d-flex min-vh-100">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-navy-900">Student & Staff Service Portal</h2>
                    <p class="text-muted">Welcome, {{ $user->name }} (Role: <span class="badge bg-primary">{{ $user->role }}</span>)</p>
                </div>
                <div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success border-0 rounded-3 small mb-4">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                </div>
            @endif

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card p-4 shadow-sm border-0 rounded-3 h-100">
                        <h5 class="fw-bold text-navy-900 mb-2"><i class="bi bi-plus-circle text-warning me-2"></i> Submit Maintenance Request</h5>
                        <p class="text-muted small">Report faulty lighting, plumbing leakages, classroom equipment, or Wi-Fi connectivity issues.</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-4 shadow-sm border-0 rounded-3 h-100">
                        <h5 class="fw-bold text-navy-900 mb-2"><i class="bi bi-shield-lock text-success me-2"></i> Account Security & 2FA</h5>
                        <p class="text-muted small">Your account is secured with 2FA email verification on sign in.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


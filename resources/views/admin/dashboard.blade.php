<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Headworld Campus Operations</title>
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
                    <h2 class="fw-bold text-navy-900">Admin Operations Dashboard</h2>
                    <p class="text-muted">Welcome, {{ $user->name }} (Role: <span class="badge bg-danger">{{ $user->role }}</span>)</p>
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
                <div class="col-md-4">
                    <div class="card p-3 shadow-sm border-0 rounded-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Campus Users</div>
                                <div class="fs-4 fw-bold">Active</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 shadow-sm border-0 rounded-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-3">
                                <i class="bi bi-tools"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Facility Technicians</div>
                                <div class="fs-4 fw-bold">Ready</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 shadow-sm border-0 rounded-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-3">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <div class="text-muted small">2FA Authentication</div>
                                <div class="fs-4 fw-bold text-success">Active & Verified</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


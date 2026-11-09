<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard | Headworld Campus Operations</title>
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
                    <h2 class="fw-bold text-navy-900">Facility Maintenance Officer Portal</h2>
                    <p class="text-muted">Welcome, {{ $user->name }} (Role: <span class="badge bg-warning text-dark">{{ $user->role }}</span>)</p>
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

            <div class="card p-4 shadow-sm border-0 rounded-3">
                <h5 class="fw-bold text-navy-900 mb-2"><i class="bi bi-wrench me-2 text-warning"></i> Assigned Maintenance Work Orders</h5>
                <p class="text-muted small">No pending emergency work orders assigned right now. You are securely authenticated with 2FA.</p>
            </div>
        </div>
    </div>
</body>
</html>


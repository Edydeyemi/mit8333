<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description"
    content="Headworld Campus Operations - High-performance digital maintenance and facility service request platform for university students, faculty, and engineering staff.">
  <title>Headworld Campus Operations | University Facility & Maintenance Hub</title>

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

  <!-- Landing Top Navigation -->
  <nav class="landing-nav bg-white border-bottom py-3">
    <div class="container d-flex align-items-center justify-content-between">
      <a href="{{ url('/') }}" class="brand-logo text-decoration-none">
        <div class="brand-crest">V</div>
        <div>
          <div class="brand-title">HEADWORLD</div>
          <div class="brand-subtitle">CAMPUS OPERATIONS</div>
        </div>
      </a>

      <div class="d-flex align-items-center gap-2 gap-sm-3">
        <a href="{{ url('/docs') }}"
          class="text-navy-900 text-decoration-none d-none d-md-inline-block small fw-bold me-2">
          <i class="bi bi-code-slash me-1"></i> REST API Docs
        </a>
        <a href="{{ route('login') }}" class="btn btn-navy btn-sm px-2 px-sm-3 fw-semibold">
          <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
        </a>
        <a href="{{ route('login') }}" class="btn btn-gold btn-sm px-2 px-sm-3 text-nowrap fw-bold">
          <i class="bi bi-plus-circle me-1 d-none d-sm-inline"></i> Lodge Complaint
        </a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="landing-hero py-5 text-white mt-4"
    style="background: linear-gradient(135deg, var(--navy-900, #0a192f), var(--navy-850, #112240));">
    <div class="container position-relative py-4 mt-4">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <div class="badge bg-warning text-dark fw-bold mb-3 p-2 px-3 text-uppercase letter-spacing-1 rounded-pill">
            <i class="bi bi-shield-check me-1"></i> Official University Operations Center
          </div>
          <h1 class="display-4 fw-bold text-white mb-3">
            Exceptional campus care, <span style="color: var(--gold-500, #f59e0b);">zero paperwork delays.</span>
          </h1>
          <p class="lead text-white-50 mb-4 max-w-700">
            Say goodbye to lost paper forms, neglected WhatsApp messages, and unrecorded office visits. Headworld
            empowers students, faculty, and engineers with rapid digital fault dispatching and real-time SLA
            accountability.
          </p>

          <div class="d-flex flex-wrap gap-3 mb-4">
            <a href="{{ route('login') }}" class="btn btn-gold btn-lg px-4 shadow fw-bold">
              <i class="bi bi-box-arrow-in-right me-2"></i> Access Operations Portal
            </a>
            <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg px-4 fw-bold">
              <i class="bi bi-person-plus me-2"></i> Register Campus ID
            </a>
          </div>

          <!-- Quick Ticket Lookup Card -->
          <div class="card p-3 p-md-4 rounded-4 shadow-lg border-0 bg-white text-dark" style="max-width: 620px;">
            <div
              class="d-flex align-items-center gap-2 mb-2 text-navy-900 fw-bold small text-uppercase letter-spacing-1">
              <i class="bi bi-search text-warning"></i> Quick Public Incident Tracker
            </div>
            <form action="{{ route('login') }}" method="GET" class="d-flex flex-column flex-sm-row gap-2">
              <input type="text" name="tracking_code" class="form-control form-control-lg fs-6 font-mono"
                placeholder="Enter Tracking Code (e.g. VRTS-2026-X9K2P)" required>
              <button type="submit" class="btn btn-navy px-4 text-nowrap fw-bold">
                Track Ticket
              </button>
            </form>
            <div class="small text-muted mt-2">
              <i class="bi bi-info-circle me-1"></i> Sign in with your university credentials to view full timeline
              history and engineer notes.
            </div>
          </div>
        </div>

        <div class="col-lg-4 d-none d-lg-block">
          <div class="card bg-navy-950 border border-warning border-opacity-25 p-4 rounded-4 shadow-xl text-white">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="small text-uppercase text-warning fw-bold letter-spacing-1">SLA Benchmark Matrix</div>
              <span class="badge bg-success bg-opacity-25 text-success">Active</span>
            </div>
            <div class="mb-4">
              <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Emergency Electrical & Plumbing</span>
                <span class="text-white fw-bold">&lt; 4 Hours</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-danger" style="width: 95%;"></div>
              </div>
            </div>
            <div class="mb-4">
              <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Classroom Lecture Equipment</span>
                <span class="text-white fw-bold">&lt; 8 Hours</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-warning" style="width: 88%;"></div>
              </div>
            </div>
            <div class="mb-2">
              <div class="d-flex justify-content-between text-muted small mb-1">
                <span>General Hostel Carpentry & Wi-Fi</span>
                <span class="text-white fw-bold">&lt; 24 Hours</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-info" style="width: 92%;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Core Features Grid -->
  <section class="py-5 bg-white">
    <div class="container py-4">
      <div class="text-center max-w-700 mx-auto mb-5">
        <span
          class="badge bg-warning bg-opacity-10 text-dark fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">Modernized
          University Infrastructure</span>
        <h2 class="display-6 fw-bold text-navy-900">Engineered to Replace Chaos with Certainty</h2>
        <p class="text-muted">Explore how Headworld automates the service lifecycle from initial photo upload to final
          technician sign-off.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-4 border rounded-4 shadow-sm">
            <div class="stat-card-icon stat-icon-amber mb-3"
              style="width: 48px; height: 48px; line-height: 48px; text-align: center; border-radius: 12px; font-size: 20px;">
              <i class="bi bi-camera-fill"></i>
            </div>
            <h5 class="fw-bold text-navy-900 mb-2">Evidence Photo Dropzone</h5>
            <p class="text-muted small mb-0">Requesters upload fault photos directly from mobile devices with secure S3
              storage, eliminating guesswork and ensuring technicians carry the right replacement parts.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-4 border rounded-4 shadow-sm">
            <div class="stat-card-icon stat-icon-navy mb-3"
              style="width: 48px; height: 48px; line-height: 48px; text-align: center; border-radius: 12px; font-size: 20px;">
              <i class="bi bi-kanban"></i>
            </div>
            <h5 class="fw-bold text-navy-900 mb-2">Field Technician Kanban</h5>
            <p class="text-muted small mb-0">Maintenance officers manage their daily assigned work orders through a
              mobile-optimized Kanban pipeline with live SLA countdown timers.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-4 border rounded-4 shadow-sm">
            <div class="stat-card-icon stat-icon-emerald mb-3"
              style="width: 48px; height: 48px; line-height: 48px; text-align: center; border-radius: 12px; font-size: 20px;">
              <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h5 class="fw-bold text-navy-900 mb-2">Cryptographic Audit Trail</h5>
            <p class="text-muted small mb-0">Every state change, technician assignment, and proof attachment is
              immutably logged with actor signatures and timestamp verification.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-4 border rounded-4 shadow-sm">
            <div class="stat-card-icon stat-icon-gold mb-3"
              style="width: 48px; height: 48px; line-height: 48px; text-align: center; border-radius: 12px; font-size: 20px;">
              <i class="bi bi-bar-chart-fill"></i>
            </div>
            <h5 class="fw-bold text-navy-900 mb-2">Executive Analytics & XLSX</h5>
            <p class="text-muted small mb-0">Deans and facility directors view incident recurrence by building,
              bottleneck analysis, and export certified reports in formatted Excel spreadsheets.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-4 border rounded-4 shadow-sm">
            <div class="stat-card-icon stat-icon-amber mb-3"
              style="width: 48px; height: 48px; line-height: 48px; text-align: center; border-radius: 12px; font-size: 20px;">
              <i class="bi bi-bell-fill"></i>
            </div>
            <h5 class="fw-bold text-navy-900 mb-2">Queued Multi-Channel Alerts</h5>
            <p class="text-muted small mb-0">Instant database in-app notifications and background queued email
              dispatches notify students and officers at every ticket milestone.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-4 border rounded-4 shadow-sm">
            <div class="stat-card-icon stat-icon-navy mb-3"
              style="width: 48px; height: 48px; line-height: 48px; text-align: center; border-radius: 12px; font-size: 20px;">
              <i class="bi bi-code-slash"></i>
            </div>
            <h5 class="fw-bold text-navy-900 mb-2">RESTful Integration API</h5>
            <p class="text-muted small mb-0">Programmatic Sanctum-secured REST API v1 complete with interactive OpenAPI
              3.0 documentation and an importable Postman collection.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Banner -->
  <section class="py-5 text-white position-relative" style="background: var(--navy-950, #030712);">
    <div class="container text-center py-4">
      <h2 class="display-6 fw-bold text-white mb-3">Ready to report a campus facility issue?</h2>
      <p class="text-white-50 max-w-600 mx-auto mb-4">
        Sign in to your university portal account or register in under 60 seconds.
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="{{ route('login') }}" class="btn btn-gold btn-lg px-4 fw-bold">
          <i class="bi bi-shield-lock-fill me-1"></i> Sign In to Portal
        </a>
        <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg px-4 fw-bold">
          <i class="bi bi-person-plus me-1"></i> Register Campus Account
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="app-footer border-top bg-white py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        <div class="brand-crest"
          style="width:28px; height:28px; font-size:0.9rem; line-height: 28px; text-align: center;">V</div>
        <span class="fw-bold text-navy-900">Headworld Campus Operations</span> &copy; 2026 Headworld University
        Facilities.
      </div>
      <div class="d-flex align-items-center gap-3 small">
        <a href="{{ url('/docs') }}" class="text-muted text-decoration-none">REST API Docs</a>
        <span class="text-muted">&bull;</span>
        <a href="{{ route('login') }}" class="text-muted text-decoration-none">Staff Login</a>
      </div>
    </div>
  </footer>

</body>

</html>
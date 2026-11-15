<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Headworld RESTful API Specification | Interactive Explorer</title>

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

<body style="background: var(--bg-body, #f4f7fb);">

  <!-- Navbar -->
  <nav class="landing-nav position-static bg-white border-bottom py-3">
    <div class="container d-flex align-items-center justify-content-between">
      <a href="{{ url('/') }}" class="brand-logo text-decoration-none" aria-label="HeadWorld Campus Operations">
        <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="HeadWorld Facility Management" class="brand-logo-img" style="height: 42px; max-width: 260px;">
      </a>

      <div class="d-flex align-items-center gap-3">
        @auth
          <a href="{{ route('login') }}" class="btn btn-gold btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Return to Portal
          </a>
        @else
          <a href="{{ route('login') }}" class="btn btn-navy btn-sm">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
          </a>
        @endauth
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <!-- Header Hero -->
    <div class="card-modern p-4 p-md-5 mb-4"
      style="background: linear-gradient(135deg, var(--navy-900, #0a192f), var(--navy-850, #112240)); color: #ffffff;">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <span class="badge bg-warning text-dark fw-bold mb-2 font-mono">OAS 3.0.3 &bull; RESTful HTTPS</span>
          <h1 class="display-6 fw-bold text-white mb-2">Headworld Operations API</h1>
          <p class="text-white-50 mb-0 max-w-700">
            Programmatic access to incident dispatch, technician triage queues, and campus facility monitoring.
            Test live endpoints directly against the operational Laravel backend.
          </p>
        </div>
        <div class="d-flex flex-column gap-2 text-md-end">
          <span class="badge bg-success bg-opacity-25 text-success font-mono p-2">Base URL: {{ url('/api/v1') }}</span>
          <div class="d-flex gap-2">
            <a href="{{ url('/docs/openapi.yaml') }}" download class="btn btn-sm btn-outline-light">
              <i class="bi bi-filetype-yml me-1"></i> openapi.yaml
            </a>
            <a href="{{ url('/docs/postman_collection.json') }}" download class="btn btn-sm btn-outline-light">
              <i class="bi bi-box-arrow-down me-1"></i> Postman Collection
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Token Input -->
    <div class="card-modern p-3 mb-4">
      <div class="row g-2 align-items-center">
        <div class="col-md-3">
          <label class="form-label fw-bold mb-0 small text-navy-900">
            <i class="bi bi-key-fill text-warning me-1"></i> Sanctum Bearer Token:
          </label>
        </div>
        <div class="col-md-9">
          <input type="text" id="liveBearerToken" class="form-control font-mono form-control-sm"
            placeholder="Paste your Sanctum Bearer Token here for authenticated tests...">
        </div>
      </div>
    </div>

    <!-- API Endpoints Accordion List -->
    <div class="d-flex flex-column gap-3">

      <!-- Endpoint 1: GET /categories -->
      <div class="api-endpoint-card">
        <div class="endpoint-header" onclick="toggleEndpoint('epCategories')">
          <span class="method-badge method-get">GET</span>
          <span class="endpoint-path">/api/v1/categories</span>
          <span class="endpoint-summary">Query active campus maintenance categories</span>
          <i class="bi bi-chevron-down ms-2"></i>
        </div>
        <div class="endpoint-body" id="epCategories" style="display: block;">
          <p class="small text-muted mb-3">Public reference endpoint. Returns all active categories, service icons, and
            SLA resolution hours.</p>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-navy-900">Live Interactive Request</span>
            <button type="button" class="btn btn-sm btn-gold" onclick="executeGetCategories()">
              <i class="bi bi-play-fill"></i> Execute Live Test
            </button>
          </div>
          <pre class="code-block-dark p-3 rounded" id="resCategories"
            style="background: #0f172a; color: #38bdf8; max-height: 250px; overflow-y: auto;">Click "Execute Live Test" to fetch live JSON payload...</pre>
        </div>
      </div>

      <!-- Endpoint 2: GET /requests -->
      <div class="api-endpoint-card">
        <div class="endpoint-header" onclick="toggleEndpoint('ep1')">
          <span class="method-badge method-get">GET</span>
          <span class="endpoint-path">/api/v1/requests</span>
          <span class="endpoint-summary">Query paginated list of maintenance requests</span>
          <i class="bi bi-chevron-down ms-2"></i>
        </div>
        <div class="endpoint-body" id="ep1" style="display: none;">
          <p class="small text-muted mb-3">Fetches campus service complaints. Supports query filters by status,
            category, priority, and building.</p>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-navy-900">Live Interactive Request</span>
            <button type="button" class="btn btn-sm btn-gold" onclick="executeGetRequests()">
              <i class="bi bi-play-fill"></i> Execute Live Test
            </button>
          </div>
          <pre class="code-block-dark p-3 rounded" id="resGetRequests"
            style="background: #0f172a; color: #38bdf8; max-height: 250px; overflow-y: auto;">Click "Execute Live Test" to fetch live JSON payload from active server...</pre>
        </div>
      </div>

      <!-- Endpoint 3: POST /requests -->
      <div class="api-endpoint-card">
        <div class="endpoint-header" onclick="toggleEndpoint('ep2')">
          <span class="method-badge method-post">POST</span>
          <span class="endpoint-path">/api/v1/requests</span>
          <span class="endpoint-summary">Lodge a new campus facility complaint</span>
          <i class="bi bi-chevron-down ms-2"></i>
        </div>
        <div class="endpoint-body" id="ep2" style="display: none;">
          <p class="small text-muted mb-3">Creates an incident record, generates a tracking reference code, and
            dispatches to facility technicians.</p>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-navy-900">Sample Payload Schema</span>
          </div>
          <pre class="code-block-dark p-3 rounded" id="resPostRequest" style="background: #0f172a; color: #38bdf8;">
{
  "service_category_id": 1,
  "priority": "emergency",
  "building": "Queen's Hall",
  "wing_floor": "Level 2",
  "room_number": "Room 204",
  "title": "Sparking fuse box on 2nd floor",
  "description": "Continuous electric humming with visible sparks near classroom doorway."
}
          </pre>
        </div>
      </div>

      <!-- Endpoint 4: POST /auth/login -->
      <div class="api-endpoint-card">
        <div class="endpoint-header" onclick="toggleEndpoint('ep4')">
          <span class="method-badge method-post">POST</span>
          <span class="endpoint-path">/api/v1/auth/login</span>
          <span class="endpoint-summary">Verify campus identity & dispatch 2FA OTP</span>
          <i class="bi bi-chevron-down ms-2"></i>
        </div>
        <div class="endpoint-body" id="ep4" style="display: none;">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-navy-900">Credentials Body</span>
          </div>
          <pre class="code-block-dark p-3 rounded" id="resAuthLogin" style="background: #0f172a; color: #38bdf8;">
{
  "email": "student@headworld.edu",
  "password": "••••••••"
}
          </pre>
        </div>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <footer class="app-footer border-top bg-white py-4 mt-5">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <div>&copy; 2026 Headworld Campus Operations. Open Institutional API.</div>
      <div class="d-flex gap-3">
        <a href="{{ url('/') }}" class="text-muted text-decoration-none">Homepage</a>
        <a href="{{ route('login') }}" class="text-muted text-decoration-none">Sign In</a>
      </div>
    </div>
  </footer>

  <script>
    function toggleEndpoint(id) {
      const el = document.getElementById(id);
      el.style.display = el.style.display === "none" ? "block" : "none";
    }

    async function executeGetCategories() {
      const output = document.getElementById("resCategories");
      output.innerText = "Loading...";
      try {
        const res = await fetch('{{ url("/api/v1/categories") }}');
        const data = await res.json();
        output.innerText = "// HTTP " + res.status + " " + res.statusText + "\n" + JSON.stringify(data, null, 2);
      } catch (e) {
        output.innerText = "// Error: " + e.message;
      }
    }

    async function executeGetRequests() {
      const output = document.getElementById("resGetRequests");
      const token = document.getElementById("liveBearerToken").value.trim();
      output.innerText = "Loading...";

      const headers = { 'Accept': 'application/json' };
      if (token) {
        headers['Authorization'] = 'Bearer ' + token;
      }

      try {
        const res = await fetch('{{ url("/api/v1/requests") }}', { headers });
        const data = await res.json();
        output.innerText = "// HTTP " + res.status + " " + res.statusText + "\n" + JSON.stringify(data, null, 2);
      } catch (e) {
        output.innerText = "// Error: " + e.message;
      }
    }
  </script>
</body>

</html>
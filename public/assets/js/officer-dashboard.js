/**
 * Headworld Campus Operations - Maintenance Officer Field Desk Controller
 */

document.addEventListener("DOMContentLoaded", () => {
  const payloadEl = document.getElementById("officerDashboardPayload");
  if (!payloadEl) return;

  let initialData = {};
  try {
    initialData = JSON.parse(payloadEl.textContent || "{}");
  } catch (e) {
    console.error("Failed to parse officer dashboard initial data:", e);
  }

  const pollUrl = payloadEl.dataset.pollUrl || "/facility/dashboard/data";

  const renderOfficerCharts = (data) => {
    if (!data) return;
    const colors = DashboardCharts.getThemeColors();

    // 1. Weekly Work Order Velocity Chart (Assigned vs Resolved)
    if (data.velocity) {
      DashboardCharts.renderBar("chartOfficerVelocity", data.velocity.labels, [
        {
          label: "Assigned",
          data: data.velocity.assigned,
          backgroundColor: colors.navy,
          borderRadius: 6
        },
        {
          label: "Resolved",
          data: data.velocity.resolved,
          backgroundColor: colors.gold,
          borderRadius: 6
        }
      ]);
    }

    // 2. My Tasks by Category Doughnut Chart
    if (data.categories) {
      DashboardCharts.renderDoughnut(
        "chartOfficerCategories",
        data.categories.labels,
        data.categories.counts,
        data.categories.colors
      );
    }
  };

  const updateKpis = (kpis) => {
    if (!kpis) return;
    const assignedEl = document.getElementById("kpiMyAssigned");
    const emergencyEl = document.getElementById("kpiMyEmergency");
    const resolvedEl = document.getElementById("kpiMyResolved");
    const slaEl = document.getElementById("kpiMySla");

    if (assignedEl && kpis.myAssigned !== undefined) assignedEl.textContent = kpis.myAssigned;
    if (emergencyEl && kpis.myEmergency !== undefined) emergencyEl.textContent = kpis.myEmergency;
    if (resolvedEl && kpis.myResolved !== undefined) resolvedEl.textContent = kpis.myResolved;
    if (slaEl && kpis.slaComplianceRate !== undefined) slaEl.textContent = `${kpis.slaComplianceRate}%`;
  };

  const updateEmergencyBanner = (ticket) => {
    const container = document.getElementById("officerEmergencyBannerContainer");
    if (!container) return;

    if (!ticket) {
      container.innerHTML = "";
      return;
    }

    container.innerHTML = `
      <div class="alert alert-danger border-0 rounded-4 shadow-sm p-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 animate-pulse">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-danger text-white rounded-circle p-3 d-flex align-items-center justify-content-center">
            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-danger">Immediate Action Required: ${ticket.title}</h5>
            <p class="small text-danger mb-0 opacity-75">
              Ticket <strong>${ticket.tracking_code}</strong> &bull; Location: ${ticket.building} (${ticket.room_number || "Unspecified"}). SLA Target: &lt; 2 Hours.
            </p>
          </div>
        </div>
        <a href="${ticket.show_url}" class="btn btn-danger text-nowrap px-3">
          <i class="bi bi-tools me-1"></i> Start Emergency Work
        </a>
      </div>
    `;
  };

  const updateQueueTable = (queue) => {
    const tbody = document.getElementById("officerQueueTbody");
    const queueCountBadge = document.getElementById("officerQueueCountBadge");
    if (!tbody || !Array.isArray(queue)) return;

    if (queueCountBadge) {
      queueCountBadge.textContent = queue.length;
    }

    if (queue.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="text-center py-5 text-muted">
            <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
            <div class="fw-bold">No Pending Work Orders</div>
            <p class="small text-muted mb-0">Your dispatch queue is all caught up!</p>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = queue.map(item => `
      <tr>
        <td>
          <a href="${item.show_url}" class="fw-bold font-mono text-navy-900">
            ${item.tracking_code}
          </a>
        </td>
        <td>
          <span class="badge bg-secondary text-navy-900 border">
            <i class="bi ${item.category_icon} me-1" style="color: ${item.category_color};"></i>
            ${item.category_name}
          </span>
        </td>
        <td>
          <div class="fw-semibold text-truncate" style="max-width:260px;" title="${item.title}">${item.title}</div>
          <small class="text-muted">${item.building} &bull; ${item.room_number || "N/A"}</small>
        </td>
        <td>
          <span class="${item.priority_badge_class}">
            ${item.priority}
          </span>
        </td>
        <td>
          <span class="${item.status_badge_class}">
            <span class="status-dot"></span>${item.status_label}
          </span>
        </td>
        <td>
          <div class="small fw-semibold">${item.submitter_name}</div>
        </td>
        <td class="text-end text-nowrap">
          <a href="${item.show_url}" class="btn btn-sm btn-gold py-1 px-3">
            <i class="bi bi-arrow-right-circle me-1"></i> Open Task
          </a>
        </td>
      </tr>
    `).join("");
  };

  const updateSyncIndicator = () => {
    const syncBadge = document.getElementById("officerSyncBadge");
    if (syncBadge) {
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
      syncBadge.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-inline-flex align-items-center gap-1"><span class="status-dot bg-success"></span> Live Sync: ${timeStr}</span>`;
    }
  };

  // Initial render
  renderOfficerCharts(initialData);
  updateKpis(initialData.kpis);
  updateSyncIndicator();

  // Periodic polling (every 18 seconds)
  DashboardCharts.startLivePoller(pollUrl, (freshData) => {
    renderOfficerCharts(freshData);
    updateKpis(freshData.kpis);
    updateEmergencyBanner(freshData.emergencyTicket);
    updateQueueTable(freshData.queue);
    updateSyncIndicator();
  }, 18000);
});


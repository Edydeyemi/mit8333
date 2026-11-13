/**
 * Headworld Campus Operations - Student / Resident Portal Dashboard Controller
 */

document.addEventListener("DOMContentLoaded", () => {
  const payloadEl = document.getElementById("userDashboardPayload");
  if (!payloadEl) return;

  let initialData = {};
  try {
    initialData = JSON.parse(payloadEl.textContent || "{}");
  } catch (e) {
    console.error("Failed to parse user dashboard initial data:", e);
  }

  const pollUrl = payloadEl.dataset.pollUrl || "/portal/dashboard/data";

  const renderUserCharts = (data) => {
    if (!data) return;
    const colors = DashboardCharts.getThemeColors();

    // 1. My Complaints Status Distribution
    if (data.statusBreakdown) {
      DashboardCharts.renderDoughnut(
        "chartUserStatus",
        data.statusBreakdown.labels,
        data.statusBreakdown.counts,
        data.statusBreakdown.colors
      );
    }

    // 2. My Complaints by Category Bar Chart
    if (data.categoryBreakdown) {
      DashboardCharts.renderBar("chartUserCategories", data.categoryBreakdown.labels, [
        {
          label: "Tickets Logged",
          data: data.categoryBreakdown.counts,
          backgroundColor: colors.gold,
          borderRadius: 6
        }
      ]);
    }
  };

  const updateKpis = (kpis) => {
    if (!kpis) return;
    const totalEl = document.getElementById("userKpiTotal");
    const progEl = document.getElementById("userKpiInProgress");
    const resEl = document.getElementById("userKpiResolved");
    const emergEl = document.getElementById("userKpiEmergency");
    const bannerActive = document.getElementById("userActiveTicketsBanner");

    if (totalEl && kpis.totalLogged !== undefined) totalEl.textContent = kpis.totalLogged;
    if (progEl && kpis.inProgress !== undefined) progEl.textContent = kpis.inProgress;
    if (resEl && kpis.resolved !== undefined) resEl.textContent = kpis.resolved;
    if (emergEl && kpis.emergencyCount !== undefined) emergEl.textContent = kpis.emergencyCount;
    if (bannerActive && kpis.activeTickets !== undefined) bannerActive.textContent = kpis.activeTickets;
  };

  const updateRecentTable = (requests) => {
    const tbody = document.getElementById("studentActiveRequestsTbody");
    if (!tbody || !Array.isArray(requests)) return;

    if (requests.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="text-center py-5 text-muted">
            <i class="bi bi-clipboard2-check fs-1 text-muted d-block mb-2"></i>
            <div class="fw-bold mb-1">No Service Requests Found</div>
            <div class="small mb-3">You haven't lodged any campus maintenance tickets yet.</div>
            <a href="/portal/requests/create" class="btn btn-gold btn-sm">
              <i class="bi bi-plus-lg me-1"></i> Submit First Request
            </a>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = requests.map(req => `
      <tr>
        <td>
          <a href="${req.show_url}" class="fw-bold text-navy-900 font-mono">
            ${req.tracking_code}
          </a>
        </td>
        <td>
          <span class="badge bg-secondary text-navy-900 border">
            <i class="bi ${req.category_icon} me-1"></i>
            ${req.category_name}
          </span>
        </td>
        <td>
          <div class="fw-semibold text-truncate" style="max-width: 240px;" title="${req.title}">
            ${req.title}
          </div>
        </td>
        <td>
          <span class="small text-muted">${req.building}</span>
          <div class="small fw-semibold text-dark">${req.room_number || "N/A"}</div>
        </td>
        <td>
          <span class="${req.priority_badge_class}">
            ${req.priority}
          </span>
        </td>
        <td>
          <span class="${req.status_badge_class}">
            <span class="status-dot"></span>${req.status_label}
          </span>
        </td>
        <td>
          <div class="d-flex align-items-center gap-1 small">
            <i class="bi bi-person-check-fill text-success"></i>
            <span>${req.officer_name}</span>
          </div>
        </td>
        <td class="text-end">
          <a href="${req.show_url}" class="btn btn-sm btn-subtle">
            Details <i class="bi bi-chevron-right ms-1"></i>
          </a>
        </td>
      </tr>
    `).join("");
  };

  const updateSyncIndicator = () => {
    const syncBadge = document.getElementById("userSyncBadge");
    if (syncBadge) {
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
      syncBadge.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-inline-flex align-items-center gap-1"><span class="status-dot bg-success"></span> Live Sync: ${timeStr}</span>`;
    }
  };

  // Initial render
  renderUserCharts(initialData);
  updateKpis(initialData.kpis);
  updateSyncIndicator();

  // Periodic polling (every 18 seconds)
  DashboardCharts.startLivePoller(pollUrl, (freshData) => {
    renderUserCharts(freshData);
    updateKpis(freshData.kpis);
    updateRecentTable(freshData.recentRequests);
    updateSyncIndicator();
  }, 18000);
});


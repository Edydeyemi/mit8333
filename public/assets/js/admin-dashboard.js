/**
 * Headworld Campus Operations - Admin Executive Dashboard Controller
 */

document.addEventListener("DOMContentLoaded", () => {
  const payloadEl = document.getElementById("adminDashboardPayload");
  if (!payloadEl) return;

  let initialData = {};
  try {
    initialData = JSON.parse(payloadEl.textContent || "{}");
  } catch (e) {
    console.error("Failed to parse admin dashboard initial data:", e);
  }

  const pollUrl = payloadEl.dataset.pollUrl || "/admin/dashboard/data";

  const renderDashboardCharts = (data) => {
    if (!data) return;
    const colors = DashboardCharts.getThemeColors();

    // 1. Line Trend Chart
    if (data.trends) {
      DashboardCharts.renderLineTrend(
        "chartRequestsTrend",
        data.trends.labels,
        data.trends.logged,
        data.trends.resolved
      );
    }

    // 2. Category Doughnut Chart
    if (data.categories) {
      DashboardCharts.renderDoughnut(
        "chartCategoryDonut",
        data.categories.labels,
        data.categories.counts,
        data.categories.colors
      );
    }

    // 3. Officer Performance Bar Chart
    if (data.officerPerformance) {
      DashboardCharts.renderBar("chartOfficerPerf", data.officerPerformance.labels, [
        {
          label: "Completed",
          data: data.officerPerformance.completed,
          backgroundColor: colors.gold,
          borderRadius: 6
        },
        {
          label: "Active Queue",
          data: data.officerPerformance.active,
          backgroundColor: colors.navy,
          borderRadius: 6
        }
      ]);
    }
  };

  const updateKpis = (kpis) => {
    if (!kpis) return;
    const totalEl = document.getElementById("adminTotalRequests");
    const activeEl = document.getElementById("adminActiveDispatches");
    const emergEl = document.getElementById("adminEmergencyCount");
    const slaEl = document.getElementById("adminSlaRate");

    if (totalEl && kpis.totalRequests !== undefined) totalEl.textContent = kpis.totalRequests;
    if (activeEl && kpis.activeDispatches !== undefined) activeEl.textContent = kpis.activeDispatches;
    if (emergEl && kpis.emergencyCount !== undefined) emergEl.textContent = kpis.emergencyCount;
    if (slaEl && kpis.slaComplianceRate !== undefined) slaEl.textContent = `${kpis.slaComplianceRate}%`;
  };

  const updateAuditStream = (audits) => {
    const streamContainer = document.getElementById("adminAuditStream");
    if (!streamContainer || !Array.isArray(audits)) return;

    if (audits.length === 0) {
      streamContainer.innerHTML = `
        <div class="p-3 text-center text-muted small bg-light rounded-3">
          <i class="bi bi-shield-check fs-4 d-block mb-1 text-muted"></i>
          No recent audit events
        </div>
      `;
      return;
    }

    streamContainer.innerHTML = audits.map(l => `
      <div class="p-2 py-1.5 rounded-2 bg-light border small transition-all">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong class="text-navy-900" style="font-size: 0.82rem;">${l.actor || "System"}</strong>
          <span class="text-muted font-mono" style="font-size: 0.72rem;">${l.time_ago || "Just now"}</span>
        </div>
        <div class="text-muted text-truncate" style="font-size: 0.8rem;" title="${l.details || l.action}">${l.details || l.action}</div>
      </div>
    `).join("");
  };

  const updateSyncIndicator = () => {
    const syncBadge = document.getElementById("dashboardSyncBadge");
    if (syncBadge) {
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
      syncBadge.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-inline-flex align-items-center gap-1"><span class="status-dot bg-success"></span> Live Sync: ${timeStr}</span>`;
    }
  };

  // Initial render with server-side hydrated payload
  renderDashboardCharts(initialData);
  updateKpis(initialData.kpis);
  updateAuditStream(initialData.recentAudits);
  updateSyncIndicator();

  // Start periodic live polling (every 18 seconds)
  DashboardCharts.startLivePoller(pollUrl, (freshData) => {
    renderDashboardCharts(freshData);
    updateKpis(freshData.kpis);
    updateAuditStream(freshData.recentAudits);
    updateSyncIndicator();
  }, 18000);
});


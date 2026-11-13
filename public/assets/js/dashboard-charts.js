/**
 * Headworld Campus Operations - Dynamic Dashboard Charts & Live Polling Engine
 * Built with Chart.js v4
 */

const DashboardCharts = {
  instances: {},
  reRenderCallbacks: {},

  getThemeColors() {
    const isDark = document.documentElement.getAttribute("data-theme") === "dark";
    return {
      textColor: isDark ? "#94a3b8" : "#64748b",
      gridColor: isDark ? "rgba(255, 255, 255, 0.08)" : "rgba(15, 29, 54, 0.06)",
      borderColor: isDark ? "#1e293b" : "#ffffff",
      tooltipBg: isDark ? "#0f172a" : "#080e1a",
      gold: "#c59b27",
      navy: isDark ? "#4f86d8" : "#0f1d36",
      emerald: "#10b981",
      amber: "#f59e0b",
      rose: "#f43f5e",
      purple: "#8b5cf6",
      sky: "#0284c7"
    };
  },

  destroy(canvasId) {
    if (this.instances[canvasId]) {
      this.instances[canvasId].destroy();
      delete this.instances[canvasId];
    }
  },

  /**
   * Render or update 7-day Incident Influx vs Resolution Velocity Line Chart
   */
  renderLineTrend(canvasId, labels, loggedData, resolvedData) {
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === "undefined") return;

    this.reRenderCallbacks[canvasId] = () => this.renderLineTrend(canvasId, labels, loggedData, resolvedData);

    const colors = this.getThemeColors();
    const ctx = el.getContext("2d");

    this.destroy(canvasId);

    this.instances[canvasId] = new Chart(ctx, {
      type: "line",
      data: {
        labels: labels || ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        datasets: [
          {
            label: "Logged Incidents",
            data: loggedData || [0, 0, 0, 0, 0, 0, 0],
            borderColor: colors.navy,
            backgroundColor: "rgba(15, 29, 54, 0.08)",
            borderWidth: 2.5,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: colors.navy,
            pointRadius: 4,
            pointHoverRadius: 6
          },
          {
            label: "Resolved",
            data: resolvedData || [0, 0, 0, 0, 0, 0, 0],
            borderColor: colors.gold,
            backgroundColor: "rgba(197, 155, 39, 0.08)",
            borderWidth: 2.5,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: colors.gold,
            pointRadius: 4,
            pointHoverRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false
        },
        plugins: {
          legend: {
            position: "top",
            labels: {
              color: colors.textColor,
              font: { family: "system-ui, -apple-system, sans-serif", weight: 600 }
            }
          },
          tooltip: {
            backgroundColor: colors.tooltipBg,
            titleFont: { weight: "bold" },
            padding: 10,
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            grid: { color: colors.gridColor },
            ticks: { color: colors.textColor }
          },
          y: {
            beginAtZero: true,
            grid: { color: colors.gridColor },
            ticks: {
              color: colors.textColor,
              precision: 0
            }
          }
        }
      }
    });
  },

  /**
   * Render or update Doughnut Breakdown Chart
   */
  renderDoughnut(canvasId, labels, data, customColors = null) {
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === "undefined") return;

    this.reRenderCallbacks[canvasId] = () => this.renderDoughnut(canvasId, labels, data, customColors);

    const colors = this.getThemeColors();
    const ctx = el.getContext("2d");

    this.destroy(canvasId);

    const defaultPalette = [
      colors.amber,
      colors.sky,
      colors.gold,
      colors.navy,
      colors.purple,
      colors.emerald,
      colors.rose
    ];

    this.instances[canvasId] = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: labels || [],
        datasets: [{
          data: data || [],
          backgroundColor: customColors && customColors.length ? customColors : defaultPalette,
          borderWidth: 2,
          borderColor: colors.borderColor
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "68%",
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              color: colors.textColor,
              font: { size: 11 },
              boxWidth: 12,
              padding: 12
            }
          },
          tooltip: {
            backgroundColor: colors.tooltipBg,
            padding: 10,
            cornerRadius: 8
          }
        }
      }
    });
  },

  /**
   * Render or update Multi-bar Workload / Comparison Chart
   */
  renderBar(canvasId, labels, datasets) {
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === "undefined") return;

    this.reRenderCallbacks[canvasId] = () => this.renderBar(canvasId, labels, datasets);

    const colors = this.getThemeColors();
    const ctx = el.getContext("2d");

    this.destroy(canvasId);

    this.instances[canvasId] = new Chart(ctx, {
      type: "bar",
      data: {
        labels: labels || [],
        datasets: datasets || []
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "top",
            labels: {
              color: colors.textColor,
              font: { weight: 600 }
            }
          },
          tooltip: {
            backgroundColor: colors.tooltipBg,
            padding: 10,
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: colors.textColor }
          },
          y: {
            beginAtZero: true,
            grid: { color: colors.gridColor },
            ticks: {
              color: colors.textColor,
              precision: 0
            }
          }
        }
      }
    });
  },

  /**
   * Periodic live widget poller
   */
  startLivePoller(endpoint, onUpdate, intervalMs = 20000) {
    if (!endpoint || typeof onUpdate !== "function") return;

    const poll = async () => {
      try {
        const res = await fetch(endpoint, {
          headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
          }
        });

        if (res.ok) {
          const data = await res.json();
          onUpdate(data);
        }
      } catch (e) {
        console.debug("Dashboard live poller jitter:", e);
      }
    };

    // Return the timer ID in case cancellation is needed
    return setInterval(poll, intervalMs);
  }
};

// Global Theme Change Listener
window.addEventListener("headworld:themeChanged", () => {
  Object.keys(DashboardCharts.reRenderCallbacks).forEach(id => {
    if (document.getElementById(id) && typeof DashboardCharts.reRenderCallbacks[id] === "function") {
      DashboardCharts.reRenderCallbacks[id]();
    }
  });
});

window.addEventListener("headworld:themeChanged", () => {
  Object.keys(DashboardCharts.reRenderCallbacks).forEach(id => {
    if (document.getElementById(id) && typeof DashboardCharts.reRenderCallbacks[id] === "function") {
      DashboardCharts.reRenderCallbacks[id]();
    }
  });
});


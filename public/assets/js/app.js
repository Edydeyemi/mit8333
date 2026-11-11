/**
 * Headworld Campus Operations - MIT8333 CA Project
 * Edward Babatunde aka Headworld
 * Copyright 2026 Edward Babatunde
 * License: MIT
 */

const App = {
  csrfToken: null,

  init() {
    this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    this.setupSidebar();
    this.setupThemeToggle();
    this.highlightActiveNav();
    this.setupNotifications();
  },

  setupSidebar() {
    const toggleBtn = document.querySelector(".header-toggle-btn");
    const sidebar = document.querySelector(".app-sidebar");
    const backdrop = document.querySelector(".sidebar-backdrop");
    const closeBtn = document.querySelector(".sidebar-close-btn");

    const closeSidebar = () => {
      if (sidebar) sidebar.classList.remove("show");
      if (backdrop) backdrop.classList.remove("show");
      document.body.style.overflow = "";
    };

    const openSidebar = () => {
      if (sidebar) sidebar.classList.add("show");
      if (backdrop) backdrop.classList.add("show");
      document.body.style.overflow = "hidden";
    };

    if (toggleBtn) {
      toggleBtn.addEventListener("click", () => {
        if (sidebar && sidebar.classList.contains("show")) {
          closeSidebar();
        } else {
          openSidebar();
        }
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener("click", closeSidebar);
    }

    if (backdrop) {
      backdrop.addEventListener("click", closeSidebar);
    }

    // Auto-close drawer on link click in mobile view
    document.querySelectorAll(".nav-link-custom").forEach(link => {
      link.addEventListener("click", () => {
        if (window.innerWidth < 992) {
          closeSidebar();
        }
      });
    });

    // Close on Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeSidebar();
    });
  },

  setupThemeToggle() {
    document.querySelectorAll(".theme-toggle-btn").forEach(btn => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        if (typeof ThemeManager !== "undefined") {
          ThemeManager.toggle();
        }
      });
    });
  },

  highlightActiveNav() {
    const currentPath = window.location.pathname;
    document.querySelectorAll(".nav-link-custom").forEach(link => {
      const href = link.getAttribute("href");
      if (href && (currentPath === href || currentPath.startsWith(href) && href !== "/admin" && href !== "/")) {
        link.classList.add("active");
      }
    });
  },

  // Notification Subsystem & Poller
  setupNotifications() {
    const badge = document.getElementById("headerNotificationBadge");
    const list = document.getElementById("headerNotificationList");
    const markAllBtn = document.getElementById("btnMarkAllReadDropdown");

    if (!badge || !list) {
      return; // Not an authenticated admin page
    }

    const fetchNotifications = async () => {
      try {
        const response = await fetch("/admin/notifications/active", {
          headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": this.csrfToken || ""
          }
        });

        if (!response.ok) return;

        const data = await response.json();
        this.renderNotifications(data);
      } catch (err) {
        // Silent fail during network jitter
        console.debug("Notification poll error:", err);
      }
    };

    // Initial fetch
    fetchNotifications();

    // Poll every 20 seconds
    setInterval(fetchNotifications, 20000);

    // Mark all as read button
    if (markAllBtn) {
      markAllBtn.addEventListener("click", async (e) => {
        e.preventDefault();
        try {
          const res = await fetch("/admin/notifications/mark-all-read", {
            method: "POST",
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest",
              "X-CSRF-TOKEN": this.csrfToken || ""
            }
          });

          if (res.ok) {
            this.renderNotifications({ unread_count: 0, notifications: [] });
            if (typeof Toast !== "undefined") {
              Toast.success("Updated", "All alerts marked as read.");
            }
          }
        } catch (err) {
          console.error(err);
        }
      });
    }
  },

  renderNotifications(data) {
    const headerBadge = document.getElementById("headerNotificationBadge");
    const sidebarBadge = document.getElementById("sidebarNotificationBadge");
    const list = document.getElementById("headerNotificationList");

    const count = data.unread_count || 0;

    // Header badge
    if (headerBadge) {
      if (count > 0) {
        headerBadge.textContent = count > 99 ? "99+" : count;
        headerBadge.classList.remove("d-none");
      } else {
        headerBadge.classList.add("d-none");
      }
    }

    // Sidebar badge
    if (sidebarBadge) {
      if (count > 0) {
        sidebarBadge.textContent = count;
        sidebarBadge.classList.remove("d-none");
      } else {
        sidebarBadge.classList.add("d-none");
      }
    }

    if (!list) return;

    if (!data.notifications || data.notifications.length === 0) {
      list.innerHTML = `
        <div class="text-center py-4 text-muted small" id="headerNotificationEmpty">
          <i class="bi bi-bell-slash fs-3 d-block mb-1"></i>
          No unread notifications
        </div>
      `;
      return;
    }

    list.innerHTML = data.notifications.map(item => `
      <div class="p-2 border-bottom d-flex align-items-start gap-2 bg-light bg-opacity-25" id="notif-item-${item.id}">
        <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.9rem;">
          <i class="bi ${item.icon || 'bi-bell-fill'}"></i>
        </div>
        <div class="flex-grow-1" style="min-width: 0;">
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-navy-900 small text-truncate" style="max-width: 180px;">${item.title}</span>
            <span class="text-muted font-mono" style="font-size: 0.68rem;">${item.time_ago}</span>
          </div>
          <div class="text-muted small text-truncate" style="font-size: 0.75rem;">${item.message}</div>
          <div class="mt-1 d-flex gap-2 align-items-center">
            ${item.url ? `<a href="${item.url}" class="small text-primary text-decoration-none" style="font-size: 0.7rem;"><i class="bi bi-box-arrow-up-right"></i> Open</a>` : ''}
            <button type="button" class="btn btn-link p-0 text-muted small btn-mark-notif-read" data-id="${item.id}" style="font-size: 0.7rem; text-decoration: none;">
              <i class="bi bi-check2"></i> Mark read
            </button>
          </div>
        </div>
      </div>
    `).join("");

    // Bind mark individual item read
    list.querySelectorAll(".btn-mark-notif-read").forEach(btn => {
      btn.addEventListener("click", async function (e) {
        e.preventDefault();
        const id = this.getAttribute("data-id");
        try {
          const res = await fetch(`/admin/notifications/${id}/read`, {
            method: "POST",
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest",
              "X-CSRF-TOKEN": App.csrfToken || ""
            }
          });
          if (res.ok) {
            const row = document.getElementById(`notif-item-${id}`);
            if (row) row.remove();
            const currentBadge = document.getElementById("headerNotificationBadge");
            if (currentBadge) {
              const currentCount = Math.max(0, parseInt(currentBadge.textContent || "1") - 1);
              if (currentCount > 0) {
                currentBadge.textContent = currentCount;
              } else {
                currentBadge.classList.add("d-none");
              }
            }
          }
        } catch (err) {
          console.error(err);
        }
      });
    });
  },

  // Badge & Pill Renderers
  renderStatusBadge(status) {
    const config = {
      submitted: { label: "Submitted", class: "badge-submitted" },
      assigned: { label: "Assigned", class: "badge-assigned" },
      in_progress: { label: "In Progress", class: "badge-in_progress" },
      on_hold: { label: "On Hold", class: "badge-on_hold" },
      completed: { label: "Resolved", class: "badge-completed" }
    };
    const c = config[status] || { label: status, class: "badge-submitted" };
    return `<span class="badge-status ${c.class}"><span class="status-dot"></span>${c.label}</span>`;
  },

  renderPriorityBadge(priority) {
    const config = {
      low: { label: "Low", class: "priority-low" },
      medium: { label: "Medium", class: "priority-medium" },
      high: { label: "High", class: "priority-high" },
      emergency: { label: "Emergency", class: "priority-emergency" }
    };
    const c = config[priority] || { label: priority, class: "priority-medium" };
    return `<span class="badge-priority ${c.class}">${c.label}</span>`;
  },

  formatDate(isoString) {
    if (!isoString) return "N/A";
    const d = new Date(isoString);
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit"
    });
  },

  timeAgo(isoString) {
    if (!isoString) return "";
    const date = new Date(isoString);
    const now = new Date();
    const diffSec = Math.floor((now - date) / 1000);

    if (diffSec < 60) return "Just now";
    if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
    if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
    return `${Math.floor(diffSec / 86400)}d ago`;
  }
};

document.addEventListener("DOMContentLoaded", () => {
  App.init();
});

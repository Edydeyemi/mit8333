/**
  * Headworld Campus Operations - MIT8333 CA Project
  * Edward Babatunde aka Headworld
  * Copyright 2026 Edward Babatunde
  * License: MIT
  *
 */

const App = {
  init() {
    this.setupSidebar();
    this.setupThemeToggle();
    this.highlightActiveNav();
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
        ThemeManager.toggle();
      });
    });
  },


  highlightActiveNav() {
    const currentPath = window.location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll(".nav-link-custom").forEach(link => {
      const href = link.getAttribute("href");
      if (href === currentPath || (currentPath === "" && href === "index.html")) {
        link.classList.add("active");
      } else {
        link.classList.remove("active");
      }
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

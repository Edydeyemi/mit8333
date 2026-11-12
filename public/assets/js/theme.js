/**
  * Headworld Campus Operations - MIT8333 CA Project
  * Edward Babatunde aka Headworld
  * Copyright 2026 Edward Babatunde
  * License: MIT
 */

const ThemeManager = {
  KEY: "headworld_theme_preference",

  init() {
    const saved = localStorage.getItem(this.KEY);
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;

    if (saved === "dark" || (!saved && prefersDark)) {
      document.documentElement.setAttribute("data-theme", "dark");
    } else {
      document.documentElement.removeAttribute("data-theme");
    }

    this.updateToggleButtons();
  },

  toggle() {
    const current = document.documentElement.getAttribute("data-theme");
    const next = current === "dark" ? "light" : "dark";

    if (next === "dark") {
      document.documentElement.setAttribute("data-theme", "dark");
    } else {
      document.documentElement.removeAttribute("data-theme");
    }

    localStorage.setItem(this.KEY, next);
    this.updateToggleButtons();

    window.dispatchEvent(new CustomEvent("headworld:themeChanged", { detail: { theme: next } }));
  },

  updateToggleButtons() {
    const isDark = document.documentElement.getAttribute("data-theme") === "dark";
    document.querySelectorAll(".theme-toggle-btn").forEach(btn => {
      btn.innerHTML = isDark
        ? '<i class="bi bi-sun-fill text-warning"></i>'
        : '<i class="bi bi-moon-stars-fill text-muted"></i>';
      btn.setAttribute("title", isDark ? "Switch to Light Mode" : "Switch to Dark Mode");
    });
  }
};

// Auto-run theme initialization
ThemeManager.init();

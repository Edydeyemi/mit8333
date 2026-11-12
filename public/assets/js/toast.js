/**
  * Headworld Campus Operations - MIT8333 CA Project
  * Edward Babatunde aka Headworld
  * Copyright 2026 Edward Babatunde
  * License: MIT
 */

const Toast = {
  container: null,

  ensureContainer() {
    if (!this.container) {
      let existing = document.querySelector(".toast-container-headworld");
      if (!existing) {
        existing = document.createElement("div");
        existing.className = "toast-container-headworld";
        document.body.appendChild(existing);
      }
      this.container = existing;
    }
    return this.container;
  },

  show(title, message, type = "info", duration = 4500) {
    const container = this.ensureContainer();

    const icons = {
      success: "bi-check-circle-fill",
      error: "bi-exclamation-triangle-fill",
      warning: "bi-exclamation-circle-fill",
      info: "bi-info-circle-fill"
    };

    const toast = document.createElement("div");
    toast.className = `toast-headworld toast-${type}`;
    toast.innerHTML = `
      <div class="toast-icon">
        <i class="bi ${icons[type] || icons.info}"></i>
      </div>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        <div class="toast-message">${message}</div>
      </div>
      <button type="button" class="toast-close" aria-label="Close">
        <i class="bi bi-x-lg"></i>
      </button>
    `;

    const closeBtn = toast.querySelector(".toast-close");
    const removeToast = () => {
      toast.style.animation = "slideOutToast 0.3s forwards";
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    };

    closeBtn.addEventListener("click", removeToast);
    container.appendChild(toast);

    if (duration > 0) {
      setTimeout(removeToast, duration);
    }
  },

  success(title, message) {
    this.show(title, message, "success");
  },

  error(title, message) {
    this.show(title, message, "error", 6000);
  },

  warning(title, message) {
    this.show(title, message, "warning");
  },

  info(title, message) {
    this.show(title, message, "info");
  }
};

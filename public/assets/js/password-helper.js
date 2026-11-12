/**
 * Headworld Campus Operations - MIT8333 CA Project
 * Password Helper: Generator, Strength Evaluator & Live Validation Cues
 * Copyright 2026 Edward Babatunde
 * License: MIT
 */

const PasswordHelper = {
  charsets: {
    upper: "ABCDEFGHJKLMNPQRSTUVWXYZ", // Excluded ambiguous I, O
    lower: "abcdefghjkmnpqrstuvwxyz", // Excluded ambiguous l, o
    numbers: "23456789",              // Excluded ambiguous 0, 1
    symbols: "!@#$%^&*()_+-="
  },

  /**
   * Cryptographically strong random password generator.
   */
  generate(length = 16) {
    const { upper, lower, numbers, symbols } = this.charsets;
    const all = upper + lower + numbers + symbols;

    // Guarantee minimum representation
    let passwordChars = [
      upper[this.getRandomInt(upper.length)],
      upper[this.getRandomInt(upper.length)],
      lower[this.getRandomInt(lower.length)],
      lower[this.getRandomInt(lower.length)],
      numbers[this.getRandomInt(numbers.length)],
      numbers[this.getRandomInt(numbers.length)],
      symbols[this.getRandomInt(symbols.length)],
      symbols[this.getRandomInt(symbols.length)],
    ];

    // Fill remaining
    for (let i = passwordChars.length; i < length; i++) {
      passwordChars.push(all[this.getRandomInt(all.length)]);
    }

    // Fisher-Yates shuffle
    for (let i = passwordChars.length - 1; i > 0; i--) {
      const j = this.getRandomInt(i + 1);
      [passwordChars[i], passwordChars[j]] = [passwordChars[j], passwordChars[i]];
    }

    return passwordChars.join("");
  },

  /**
   * Safe random integer generator using Crypto API.
   */
  getRandomInt(max) {
    if (window.crypto && window.crypto.getRandomValues) {
      const array = new Uint32Array(1);
      window.crypto.getRandomValues(array);
      return array[0] % max;
    }
    return Math.floor(Math.random() * max);
  },

  /**
   * Copy text to clipboard with notification.
   */
  async copyToClipboard(text, notify = true) {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
      } else {
        const temp = document.createElement("textarea");
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand("copy");
        document.body.removeChild(temp);
      }

      if (notify && typeof Toast !== "undefined") {
        Toast.success("Generated & Copied", "Strong password auto-filled and copied to clipboard.");
      }
    } catch (err) {
      console.warn("Clipboard access failed:", err);
    }
  },

  /**
   * Evaluate password strength and requirement rules.
   */
  evaluate(password) {
    const rules = {
      length: password.length >= 8,
      upper: /[A-Z]/.test(password),
      lower: /[a-z]/.test(password),
      number: /[0-9]/.test(password),
      symbol: /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password),
    };

    let score = 0;
    if (rules.length) score++;
    if (rules.upper) score++;
    if (rules.lower) score++;
    if (rules.number) score++;
    if (rules.symbol) score++;

    let level = "empty";
    let label = "No password";
    let cssClass = "";

    if (password.length > 0) {
      if (score <= 2) {
        level = "weak";
        label = "Weak (Insecure)";
        cssClass = "strength-weak";
      } else if (score === 3) {
        level = "fair";
        label = "Fair (Add numbers/symbols)";
        cssClass = "strength-fair";
      } else if (score === 4) {
        level = "good";
        label = "Good";
        cssClass = "strength-good";
      } else {
        level = "strong";
        label = "Strong & Secure";
        cssClass = "strength-strong";
      }
    }

    // Backend minimum check: >=8 chars, uppercase, lowercase, number
    const isValid = rules.length && rules.upper && rules.lower && rules.number;

    return { rules, score, level, label, cssClass, isValid };
  },

  /**
   * Bind live validation cues and generator controls to form elements.
   */
  bind(config) {
    const pwdInput = typeof config.password === "string" ? document.querySelector(config.password) : config.password;
    const confirmInput = typeof config.confirm === "string" ? document.querySelector(config.confirm) : config.confirm;
    const generateBtn = typeof config.generateBtn === "string" ? document.querySelector(config.generateBtn) : config.generateBtn;
    const togglePwdBtn = typeof config.togglePwdBtn === "string" ? document.querySelector(config.togglePwdBtn) : config.togglePwdBtn;
    const toggleConfirmBtn = typeof config.toggleConfirmBtn === "string" ? document.querySelector(config.toggleConfirmBtn) : config.toggleConfirmBtn;
    const strengthBar = typeof config.strengthBar === "string" ? document.querySelector(config.strengthBar) : config.strengthBar;
    const strengthText = typeof config.strengthText === "string" ? document.querySelector(config.strengthText) : config.strengthText;
    const reqContainer = typeof config.reqContainer === "string" ? document.querySelector(config.reqContainer) : config.reqContainer;
    const matchBox = typeof config.matchBox === "string" ? document.querySelector(config.matchBox) : config.matchBox;
    const form = typeof config.form === "string" ? document.querySelector(config.form) : config.form;

    if (!pwdInput) return;

    const updatePasswordCues = () => {
      const val = pwdInput.value;
      const res = this.evaluate(val);

      // 1. Update Strength Meter
      if (strengthBar) {
        strengthBar.className = "password-strength-bar " + res.cssClass;
      }
      if (strengthText) {
        strengthText.textContent = res.label;
        strengthText.className = "small font-mono " + (res.level === "weak" ? "text-danger" : res.level === "strong" ? "text-success" : "text-muted");
      }

      // 2. Update Checklist Items
      if (reqContainer) {
        this.updateReqItem(reqContainer, "length", res.rules.length);
        this.updateReqItem(reqContainer, "upper", res.rules.upper);
        this.updateReqItem(reqContainer, "lower", res.rules.lower);
        this.updateReqItem(reqContainer, "number", res.rules.number);
        this.updateReqItem(reqContainer, "symbol", res.rules.symbol);
      }

      // 3. Highlight field border
      if (val.length === 0) {
        pwdInput.classList.remove("is-valid", "is-invalid");
      } else if (res.isValid) {
        pwdInput.classList.add("is-valid");
        pwdInput.classList.remove("is-invalid");
      } else {
        pwdInput.classList.add("is-invalid");
        pwdInput.classList.remove("is-valid");
      }

      updateMatchCues();
    };

    const updateMatchCues = () => {
      if (!confirmInput || !matchBox) return;

      const pVal = pwdInput.value;
      const cVal = confirmInput.value;

      if (cVal.length === 0) {
        matchBox.innerHTML = "";
        confirmInput.classList.remove("is-valid", "is-invalid");
        return;
      }

      if (pVal === cVal) {
        matchBox.innerHTML = `<span class="match-success"><i class="bi bi-check-circle-fill"></i> Passwords match</span>`;
        confirmInput.classList.add("is-valid");
        confirmInput.classList.remove("is-invalid");
      } else {
        matchBox.innerHTML = `<span class="match-error"><i class="bi bi-exclamation-triangle-fill"></i> Passwords do not match</span>`;
        confirmInput.classList.add("is-invalid");
        confirmInput.classList.remove("is-valid");
      }
    };

    // Input listeners
    pwdInput.addEventListener("input", updatePasswordCues);
    if (confirmInput) {
      confirmInput.addEventListener("input", updateMatchCues);
    }

    // Generator click listener
    if (generateBtn) {
      generateBtn.addEventListener("click", (e) => {
        e.preventDefault();
        const generated = this.generate(16);
        pwdInput.value = generated;
        pwdInput.type = "text"; // Temporarily reveal so user sees it

        if (confirmInput) {
          confirmInput.value = generated;
          confirmInput.type = "text";
        }

        // Update toggle icons
        if (togglePwdBtn) {
          togglePwdBtn.innerHTML = '<i class="bi bi-eye-slash-fill"></i>';
        }
        if (toggleConfirmBtn) {
          toggleConfirmBtn.innerHTML = '<i class="bi bi-eye-slash-fill"></i>';
        }

        updatePasswordCues();
        this.copyToClipboard(generated, true);
      });
    }

    // Show/Hide Password Toggle
    const bindToggle = (btn, input) => {
      if (!btn || !input) return;
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash-fill"></i>' : '<i class="bi bi-eye-fill"></i>';
      });
    };

    bindToggle(togglePwdBtn, pwdInput);
    bindToggle(toggleConfirmBtn, confirmInput);

    // Form Submission Guard
    if (form) {
      form.addEventListener("submit", (e) => {
        const val = pwdInput.value;
        const isRequired = pwdInput.hasAttribute("required");

        // If field is optional and left blank, permit submission
        if (!isRequired && val.length === 0) {
          return true;
        }

        const res = this.evaluate(val);

        if (!res.isValid) {
          e.preventDefault();
          pwdInput.focus();
          if (typeof Toast !== "undefined") {
            Toast.error("Password Incomplete", "Password must be at least 8 characters and include uppercase, lowercase, and numbers.");
          }
          return false;
        }

        if (confirmInput && confirmInput.value !== val) {
          e.preventDefault();
          confirmInput.focus();
          if (typeof Toast !== "undefined") {
            Toast.error("Mismatch Error", "The confirmation password does not match.");
          }
          return false;
        }
      });
    }

    // Initial check if fields have values
    if (pwdInput.value) {
      updatePasswordCues();
    }
  },

  updateReqItem(container, ruleKey, isMet) {
    const el = container.querySelector(`[data-rule="${ruleKey}"]`);
    if (!el) return;

    if (isMet) {
      el.classList.add("met");
      el.classList.remove("unmet");
      const icon = el.querySelector("i");
      if (icon) {
        icon.className = "bi bi-check-circle-fill text-success";
      }
    } else {
      el.classList.remove("met");
      el.classList.add("unmet");
      const icon = el.querySelector("i");
      if (icon) {
        icon.className = "bi bi-circle text-muted";
      }
    }
  },

  /**
   * Auto-initialize standard forms on page load.
   */
  autoInit() {
    // 1. Public Registration Page
    const regForm = document.getElementById("registerForm");
    const regPwd = document.getElementById("regPassword");
    const regConfirm = document.getElementById("regConfirmPassword");

    if (regForm && regPwd && regConfirm) {
      this.bind({
        form: regForm,
        password: regPwd,
        confirm: regConfirm,
        generateBtn: "#btnGenerateRegPassword",
        togglePwdBtn: "#btnToggleRegPassword",
        toggleConfirmBtn: "#btnToggleRegConfirm",
        strengthBar: "#regStrengthBar",
        strengthText: "#regStrengthText",
        reqContainer: "#regPasswordReqs",
        matchBox: "#regMatchFeedback"
      });
    }

    // 2. Admin Provisioning Modal
    const addModal = document.getElementById("addUserModal");
    const addForm = document.getElementById("addRecordForm");
    const addPwd = document.getElementById("addUserPassword");
    const addConfirm = document.getElementById("addUserConfirmPassword");

    if (addModal && addPwd && addConfirm) {
      this.bind({
        form: addForm || addModal.querySelector("form"),
        password: addPwd,
        confirm: addConfirm,
        generateBtn: "#btnGenerateAddUserPassword",
        togglePwdBtn: "#btnToggleAddUserPassword",
        toggleConfirmBtn: "#btnToggleAddUserConfirm",
        strengthBar: "#addUserStrengthBar",
        strengthText: "#addUserStrengthText",
        reqContainer: "#addUserPasswordReqs",
        matchBox: "#addUserMatchFeedback"
      });
    }

    // 3. Admin Edit Personnel Modal (Password Reset Section)
    const editModal = document.getElementById("editUserModal");
    const editForm = document.getElementById("editUserForm");
    const editPwd = document.getElementById("editUserPassword");
    const editConfirm = document.getElementById("editUserConfirmPassword");

    if (editModal && editPwd && editConfirm) {
      this.bind({
        form: editForm || editModal.querySelector("form"),
        password: editPwd,
        confirm: editConfirm,
        generateBtn: "#btnGenerateEditUserPassword",
        togglePwdBtn: "#btnToggleEditUserPassword",
        toggleConfirmBtn: "#btnToggleEditUserConfirm",
        strengthBar: "#editUserStrengthBar",
        strengthText: "#editUserStrengthText",
        reqContainer: "#editUserPasswordReqs",
        matchBox: "#editUserMatchFeedback"
      });
    }
  }
};

document.addEventListener("DOMContentLoaded", () => {
  PasswordHelper.autoInit();
});

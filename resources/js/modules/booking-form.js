/**
 * BookingForm.js
 */
export default class BookingForm {
  constructor(formId) {
    this.form = document.getElementById(formId);
    if (!this.form) return console.error(`Form #${formId} not found`);

    this.submitBtn = this.form.querySelector("#submitBtn");
    this.msgBox = document.getElementById("messageBox");
    this.userSelect = this.form.querySelector("#user_id");
    this.clientSelect = this.form.querySelector("#client_id");
    this.csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content") ?? "";

    this.api = {
      users: "/api/users",
      clients: "/api/clients",
      bookings: "/api/bookings",
    };
  }

  async init() {
    try {
      await Promise.all([
        this.populateSelect(this.api.users, this.userSelect, "user"),
        this.populateSelect(this.api.clients, this.clientSelect, "client"),
      ]);

      // Disable submit if any required list is empty
    if (
      this.userSelect.options.length <= 1 ||
      this.clientSelect.options.length <= 1
    ) {
      this.toggleLoading(true);
      this.showMessage("Please create at least one User and Client in the DB before adding a booking.", "error");
      return;
    }

      this.form.addEventListener("submit", (e) => this.handleSubmit(e));
    } catch (err) {
      this.showMessage("Failed to load data. Please refresh.", "error");
      console.error("Init failed:", err);
    }
  }

  async request(url, options = {}) {
    const defaults = {
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": this.csrfToken,
      },
    };
    const res = await fetch(url, { ...defaults, ...options });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const message =
        data?.message ||
        Object.values(data.errors || {})[0]?.[0] ||
        `Request failed (${res.status})`;
      throw new Error(message);
    }
    return data;
  }

  async populateSelect(endpoint, selectEl, label) {
    if (!selectEl) return;
    selectEl.innerHTML = `<option>Loading ${label}s...</option>`;
    try {
      const data = await this.request(endpoint);
      if (!Array.isArray(data) || !data.length) {
        selectEl.innerHTML = `<option>No ${label}s found</option>`;
        return;
      }
      selectEl.innerHTML = `<option value="">Select ${label}...</option>`;
      data.forEach(({ id, name }) =>
        selectEl.insertAdjacentHTML(
          "beforeend",
          `<option value="${id}">${name}</option>`
        )
      );
    } catch (err) {
      selectEl.innerHTML = `<option>Error loading ${label}s</option>`;
      console.error(`Failed to load ${label}s:`, err);
    }
  }

  validateTimes({ start_time, end_time }) {
    const start = new Date(start_time);
    const end = new Date(end_time);
    if (isNaN(start) || isNaN(end))
      throw new Error("Please provide valid start and end times.");
    if (end <= start) throw new Error("End time must be after start time.");
  }

  async handleSubmit(event) {
    event.preventDefault();
    this.clearMessage();

    const data = Object.fromEntries(new FormData(this.form));
    try {
      this.validateTimes(data);
      this.toggleLoading(true);
      await this.request(this.api.bookings, {
        method: "POST",
        body: JSON.stringify(data),
      });
      this.showMessage("Booking created successfully!", "success");
      this.form.reset();
    } catch (err) {
      this.showMessage(err.message || "Submission failed.", "error");
      console.error("Submit error:", err);
    } finally {
      this.toggleLoading(false);
    }
  }

  toggleLoading(isLoading) {
    if (!this.submitBtn) return;
    this.submitBtn.disabled = isLoading;
    this.submitBtn.textContent = isLoading
      ? "Creating..."
      : "Create Booking";
  }

  showMessage(text, type = "info") {
    if (!this.msgBox) return;
    const colors = {
      success: "text-green-600",
      error: "text-red-600",
      info: "text-gray-600",
    };
    this.msgBox.textContent = text;
    this.msgBox.className = `mt-4 text-sm font-medium ${colors[type]}`;
  }

  clearMessage() {
    if (!this.msgBox) return;
    this.msgBox.textContent = "";
    this.msgBox.className = "mt-4 text-sm font-medium";
  }
}

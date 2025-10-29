// Simple toast notifier (success / error)
// Usage: notify('success'|'error', message, { duration?: number, id?: string })
(function () {
  let container;

  function ensureContainer() {
    if (!container) {
      container = document.createElement("div");
      container.className = "ntf-container";
      container.setAttribute("aria-live", "polite");
      document.body.appendChild(container);
    }
  }

  function notify(type, message, options = {}) {
    ensureContainer();

    const isError = type === "error";
    const duration = Number.isFinite(options.duration)
      ? options.duration
      : 3000;

    const toast = document.createElement("div");
    toast.className = `ntf ${isError ? "error" : "success"}`;
    toast.role = "status";
    toast.ariaAtomic = "true";
    if (options.id) toast.dataset.id = options.id;

    toast.innerHTML = `
      <span class="ntf-badge" aria-hidden="true"></span>
      <div class="ntf-content">
        <p class="ntf-title">${isError ? "Error" : "Success"}</p>
        <div class="ntf-message">${message}</div>
        <div class="ntf-actions"></div>
      </div>
      <button class="ntf-close" aria-label="Close notification">Close</button>
    `;

    const closeBtn = toast.querySelector(".ntf-close");
    let timer;

    function remove() {
      if (timer) clearTimeout(timer);
      toast.classList.add("ntf-leave");
      setTimeout(() => toast.remove(), 200);
    }
    function startTimer() {
      if (duration > 0) timer = setTimeout(remove, duration);
    }
    function stopTimer() {
      if (timer) clearTimeout(timer);
    }

    closeBtn.addEventListener("click", remove);
    toast.addEventListener("mouseenter", stopTimer);
    toast.addEventListener("mouseleave", startTimer);
    toast.addEventListener("focusin", stopTimer);
    toast.addEventListener("focusout", startTimer);

    container.appendChild(toast);
    // enter animation
    requestAnimationFrame(() => toast.classList.add("ntf-enter"));

    startTimer();
    return { close: remove, el: toast };
  }

  window.notify = notify;
})();

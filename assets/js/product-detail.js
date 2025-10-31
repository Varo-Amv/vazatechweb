// Prevent double-binding if this script loaded twice
if (window.__PD_DETAIL_INIT__) {
  // sudah inisialisasi, jangan jalan dua kali
} else {
  window.__PD_DETAIL_INIT__ = true;
  (function () {
    // <- bungkus semua kode lama Anda di dalam IIFE ini

    const qtyInput = document.getElementById("qty");
    const decBtn = document.querySelector('.pd-qty-btn[data-act="dec"]');
    const incBtn = document.querySelector('.pd-qty-btn[data-act="inc"]');
    const totalEl = document.getElementById("total");
    const stock = (window.PD_DATA && window.PD_DATA.stock) || 0;
    const price = (window.PD_DATA && window.PD_DATA.price) || 0;
    const form = document.getElementById("orderForm");
    const buyBtn = form.querySelector(".pd-buy-btn");

    function rupiah(n) {
      n = parseInt(n || 0, 10);
      return "Rp" + n.toLocaleString("id-ID");
    }

    function clampQty() {
      let q = parseInt(qtyInput.value || 1, 10);
      if (isNaN(q) || q < 1) q = 1;
      if (q > stock) q = stock;
      qtyInput.value = q;
    }

    function renderTotal() {
      clampQty();
      const q = parseInt(qtyInput.value, 10);
      totalEl.textContent = rupiah(price * q);
      buyBtn.disabled = stock <= 0 || q < 1;
    }

    decBtn?.addEventListener("click", function () {
      qtyInput.value = Math.max(1, parseInt(qtyInput.value || 1, 10) - 1);
      renderTotal();
    });

    incBtn?.addEventListener("click", function () {
      qtyInput.value = Math.min(stock, parseInt(qtyInput.value || 1, 10) + 1);
      renderTotal();
    });

    qtyInput?.addEventListener("input", renderTotal);

    // Tabs
    document.querySelectorAll(".pd-tab-headers button").forEach((btn) => {
      btn.addEventListener("click", () => {
        document
          .querySelectorAll(".pd-tab-headers button")
          .forEach((b) => b.classList.remove("active"));
        document
          .querySelectorAll(".pd-tab-panel")
          .forEach((p) => p.classList.remove("active"));
        btn.classList.add("active");
        const id = btn.getAttribute("data-tab");
        document.getElementById(id)?.classList.add("active");
      });
    });

    // Validasi minimal sebelum submit (pakai notify)
    // helper: show one notify only per id
    function notifyOnce(id, type, html, opts) {
      window.__PD_NOTIFY_OPEN = window.__PD_NOTIFY_OPEN || new Set();
      if (window.__PD_NOTIFY_OPEN.has(id)) return;
      window.__PD_NOTIFY_OPEN.add(id);
      const onClose = () => window.__PD_NOTIFY_OPEN.delete(id);
      notify(
        type,
        html,
        Object.assign({ duration: 6000 }, opts || {}, { id, onClose })
      );
    }

    form?.addEventListener("submit", function (e) {
      const userIdEl = document.getElementById("user_id");
      const zoneIdEl = document.getElementById("zone_id");
      const userId = (userIdEl?.value || "").trim();
      const zoneId = (zoneIdEl?.value || "").trim();

      const errs = [];
      if (!userId) errs.push("User ID wajib diisi.");
      if (!zoneId) errs.push("Zone ID wajib diisi.");

      if (errs.length) {
        e.preventDefault();
        notifyOnce("need-ids", "error", errs.join("<br>"));
        (!userId ? userIdEl : zoneIdEl)?.focus();
        (!userId ? userIdEl : zoneIdEl)?.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
        return false;
      }
    });

    // init
    renderTotal();
  })(); // end IIFE
}

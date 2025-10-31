(function () {
  // Cegah double-binding jika file ini ter-load dua kali
  if (window.__LIVE_SEARCH_INIT__) return;
  window.__LIVE_SEARCH_INIT__ = true;

  const input = document.getElementById("liveSearch");
  const panel = document.getElementById("searchResults");
  if (!input || !panel) return;

  const MIN = 3; // minimal 3 huruf
  const rupiah = (n) => "Rp " + (parseInt(n, 10) || 0).toLocaleString("id-ID");

  let timer = null,
    idx = -1; // untuk navigasi keyboard
  let items = [];

  function clearPanel() {
    panel.innerHTML = "";
    panel.classList.remove("show");
    panel.hidden = true;
    idx = -1;
    items = [];
  }

  function renderMessage(title, subtitle) {
    panel.hidden = false;
    panel.classList.add("show");
    panel.innerHTML = `
      <div class="search-empty" aria-live="polite">
        <div class="search-empty__icon" aria-hidden="true">🔎</div>
        <div class="search-empty__text">
          <div class="search-empty__title">${escapeHtml(title)}</div>
          ${
            subtitle
              ? `<div class="search-empty__sub">${escapeHtml(subtitle)}</div>`
              : ""
          }
        </div>
      </div>
    `;
  }

  function render(list) {
    items = list || [];
    if (!items.length) {
      renderMessage("Tidak ada hasil", "Coba kata kunci lain.");
      return;
    }
    panel.hidden = false;
    panel.classList.add("show");

    panel.innerHTML = items
      .map(
        (it, i) => `
      <div class="search-item" data-index="${i}" data-href="/product?id=${
          it.id
        }">
        <img class="search-thumb" src="${it.image_url || ""}" alt="">
        <div>
          <div class="search-title">${escapeHtml(it.product_name || "")}</div>
          <div class="search-sub">${escapeHtml(it.game || "")}</div>
        </div>
        <div class="search-price">${rupiah(it.price)}</div>
      </div>
    `
      )
      .join("");
  }

  function escapeHtml(s) {
    return (s || "").replace(
      /[&<>"']/g,
      (m) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        }[m])
    );
  }

  function search(q) {
    fetch(`/assets/api/search_stocks.php?q=${encodeURIComponent(q)}`)
      .then((r) => r.json())
      .then((d) => render(d.items || []))
      .catch(() =>
        renderMessage("Gagal memuat", "Periksa koneksi internet Anda.")
      );
  }

  // --- input handler (debounce + min 3 huruf) ---
  input.addEventListener("input", () => {
    const q = input.value.trim();
    clearTimeout(timer);

    if (!q) {
      clearPanel();
      return;
    }

    if (q.length < MIN) {
      renderMessage("Masukkan minimal 3 huruf", "Contoh: ‘genshin’");
      return;
    }

    timer = setTimeout(() => search(q), 200); // debounce 200ms
  });

  // Navigasi keyboard
  input.addEventListener("keydown", (e) => {
    if (!panel.classList.contains("show")) return;
    const rows = panel.querySelectorAll(".search-item");
    if (!rows.length) return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      setActive(Math.min(idx + 1, rows.length - 1));
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      setActive(Math.max(idx - 1, 0));
    } else if (e.key === "Enter") {
      e.preventDefault();
      const el = rows[idx] || rows[0];
      if (el?.dataset.href) window.location.href = el.dataset.href;
    } else if (e.key === "Escape") {
      clearPanel();
    }
  });

  function setActive(i) {
    const rows = panel.querySelectorAll(".search-item");
    rows.forEach((r) => r.classList.remove("active"));
    if (rows[i]) rows[i].classList.add("active");
    idx = i;

    // auto-scroll ke item aktif
    if (rows[i]) {
      const el = rows[i];
      const boxTop = panel.scrollTop;
      const boxBottom = boxTop + panel.clientHeight;
      const elTop = el.offsetTop;
      const elBottom = elTop + el.offsetHeight;
      if (elTop < boxTop) panel.scrollTop = elTop;
      else if (elBottom > boxBottom)
        panel.scrollTop = elBottom - panel.clientHeight;
    }
  }

  panel.addEventListener("click", (e) => {
    const item = e.target.closest(".search-item");
    if (item?.dataset.href) window.location.href = item.dataset.href;
  });

  document.addEventListener("click", (e) => {
    if (!panel.contains(e.target) && e.target !== input) {
      clearPanel();
    }
  });
})();

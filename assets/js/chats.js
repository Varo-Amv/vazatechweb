// assets/js/chats.js
(() => {
  const API_BASE = "../assets/api";

  const els = {
    q: document.querySelector("#q"),
    status: document.querySelector("#statusSelect"),
    list: document.querySelector("#chatList"),
    thread: document.querySelector("#thread"),
    msgBox: document.querySelector("#msgBox"),
    send: document.querySelector("#sendBtn"),
    kpiOpen: document.querySelector("#kpiOpen"),
    kpiPending: document.querySelector("#kpiPending"),
    kpiClosed: document.querySelector("#kpiClosed"),
  };

  let currentChatId = null;
  let refreshTimer = null;

  function fmtTime(s) {
    if (!s) return "";
    const d = new Date(s.replace(" ", "T"));
    return d.toLocaleString();
  }

  async function fetchJSON(url, opt) {
    const r = await fetch(url, opt);
    if (!r.ok) throw new Error(await r.text());
    return r.json();
  }

  // KPI
  async function refreshKPI() {
    try {
      const d = await fetchJSON(`${API_BASE}/chat_kpi.php`);
      if (d.ok) {
        if (els.kpiOpen) els.kpiOpen.textContent = d.open_count ?? 0;
        if (els.kpiPending) els.kpiPending.textContent = d.pending_count ?? 0;
        if (els.kpiClosed) els.kpiClosed.textContent = d.closed_count ?? 0;
      }
    } catch (e) {
      // silent
    }
  }

  // List chats
  async function loadList() {
    const q = encodeURIComponent(els.q?.value || "");
    const s = encodeURIComponent(els.status?.value || "all");
    const url = `${API_BASE}/chat_list.php?q=${q}&status=${s}&limit=100`;

    const d = await fetchJSON(url);
    if (!d.ok) return;

    els.list.innerHTML = "";
    if (!d.rows || !d.rows.length) {
      els.list.innerHTML = `<div class="empty">Tidak ada chat.</div>`;
      return;
    }

    d.rows.forEach((row) => {
      const item = document.createElement("button");
      item.className = "chat-item";
      item.dataset.id = row.id;

      item.innerHTML = `
        <div class="subject">${row.subject || "(Tanpa subjek)"}</div>
        <div class="meta">
          <span>${row.user_name || "-"}</span>
          <span class="sep">·</span>
          <span>${row.user_email || "-"}</span>
        </div>
        <div class="last">${
          row.last_message ? row.last_message.slice(0, 80) : ""
        }</div>
        <div class="time">${fmtTime(row.last_at || row.updated_at)}</div>
      `;
      item.addEventListener("click", () => openChat(row.id));
      els.list.appendChild(item);
    });
  }

  // Open chat + load messages
  async function openChat(id) {
    currentChatId = id;
    els.thread.innerHTML = `<div class="placeholder">Memuat chat...</div>`;
    clearInterval(pollTimer);

    // tandai item yang aktif
    document
      .querySelectorAll("#chatList .item.active")
      .forEach((n) => n.classList.remove("active"));
    const selected = document.querySelector(`#chatList .item[data-id="${id}"]`);
    if (selected) selected.classList.add("active");

    // set judul
    const item = selected;
    els.title.textContent = item
      ? item.querySelector(".subject").textContent
      : `Chat #${id}`;

    await loadMessages();
    pollTimer = setInterval(loadMessages, 5000);

    const d = await fetchJSON(`${API_BASE}/chat_messages.php?chat_id=${id}`);
    if (!d.ok) return;

    // header meta
    const head = document.createElement("div");
    head.className = "thread-head";
    head.innerHTML = `
      <div class="title"><strong>${
        d.meta.subject || "(Tanpa subjek)"
      }</strong></div>
      <div class="sub">${d.meta.user_name || "-"} &lt;${
      d.meta.user_email || "-"
    }&gt;</div>
      <div class="sub">Status: ${d.meta.status}</div>
      <hr/>
    `;
    els.thread.innerHTML = "";
    els.thread.appendChild(head);

    // messages
    const body = document.createElement("div");
    body.className = "messages";
    d.messages.forEach((m) => {
      const bubble = document.createElement("div");
      bubble.className = `msg ${m.sender === "admin" ? "admin" : "user"}`;
      bubble.innerHTML = `<div class="text">${escapeHTML(m.message)}</div>
         <div class="when">${fmtTime(m.created_at)}</div>`;
      body.appendChild(bubble);
    });
    els.thread.appendChild(body);
    body.scrollTop = body.scrollHeight;

    // Start auto refresh
    if (refreshTimer) clearInterval(refreshTimer);
    refreshTimer = setInterval(() => {
      if (currentChatId) reloadCurrent();
    }, 4000);
  }

  async function reloadCurrent() {
    if (!currentChatId) return;
    const d = await fetchJSON(
      `${API_BASE}/chat_messages.php?chat_id=${currentChatId}`
    );
    const body = els.thread.querySelector(".messages");
    if (!body) return;

    body.innerHTML = "";
    d.messages.forEach((m) => {
      const bubble = document.createElement("div");
      bubble.className = `msg ${m.sender === "admin" ? "admin" : "user"}`;
      bubble.innerHTML = `<div class="text">${escapeHTML(m.message)}</div>
         <div class="when">${fmtTime(m.created_at)}</div>`;
      body.appendChild(bubble);
    });
    body.scrollTop = body.scrollHeight;
  }

  // Send
  async function sendMessage() {
    const msg = (els.msgBox?.value || "").trim();
    if (!currentChatId || !msg) return;
    els.send.disabled = true;

    const form = new FormData();
    form.append("chat_id", currentChatId);
    form.append("message", msg);

    try {
      const d = await fetchJSON(`${API_BASE}/chat_send.php`, {
        method: "POST",
        body: form,
      });
      if (d.ok) {
        els.msgBox.value = "";
        await reloadCurrent();
        await loadList(); // supaya last message & updated_at ikut naik
      }
    } catch (e) {
      alert("Gagal mengirim pesan");
    } finally {
      els.send.disabled = false;
    }
  }

  // Utils
  function escapeHTML(s = "") {
    return s.replace(
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

  // Events
  els.q?.addEventListener("input", debounce(loadList, 400));
  els.status?.addEventListener("change", loadList);
  els.send?.addEventListener("click", sendMessage);
  els.msgBox?.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) {
      sendMessage();
    }
  });

  function debounce(fn, ms = 300) {
    let t;
    return (...a) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...a), ms);
    };
  }

  // Init
  loadList();
  refreshKPI();
  setInterval(refreshKPI, 5000);
})();

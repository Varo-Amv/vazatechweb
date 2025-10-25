/* revenue-charts.js — BAR, slice last N days, pretty date labels */
document.addEventListener("DOMContentLoaded", () => {
  const API =
    (typeof window !== "undefined" && window.REVENUE_API_URL) ||
    "../assets/api/revenue_series.php?days=30";

  const REFRESH_MS =
    (typeof window !== "undefined" && window.REVENUE_REFRESH_MS) || 10000;

  // tampilkan berapa hari terakhir di grafik (default 14)
  const KEEP_DAYS =
    (typeof window !== "undefined" && window.REVENUE_KEEP_DAYS) || 14;

  const elTx = document.getElementById("chartTx");
  const elRev = document.getElementById("chartRevenue");
  const elQty = document.getElementById("chartQty");

  if (!elTx || !elRev || !elQty) return;

  const fmtIdr = (v) => "Rp " + (Number(v) || 0).toLocaleString("id-ID");
  const fmtDay = (s) => {
    // "YYYY-MM-DD" -> "dd MMM"
    const d = new Date(s + "T00:00:00");
    return d.toLocaleDateString("id-ID", { day: "2-digit", month: "short" });
  };

  const commonBar = {
    borderWidth: 0,
    borderRadius: 6,
    maxBarThickness: 28,
    categoryPercentage: 0.7,
    barPercentage: 0.9,
  };

  const opts = (isMoney, suggestedMax) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 0 },
    plugins: {
      legend: { display: false },
      tooltip: {
        mode: "index",
        intersect: false,
        callbacks: isMoney ? { label: (c) => fmtIdr(c.parsed.y) } : undefined,
      },
    },
    interaction: { mode: "index", intersect: false },
    scales: {
      x: {
        grid: { display: false },
        ticks: {
          maxRotation: 0,
          autoSkip: true,
          autoSkipPadding: 10,
        },
      },
      y: {
        beginAtZero: true,
        suggestedMax,
        ticks: isMoney ? { callback: (v) => fmtIdr(v) } : undefined,
      },
    },
  });

  const txChart = new Chart(elTx, {
    type: "bar",
    data: {
      labels: [],
      datasets: [
        {
          label: "Transaksi",
          data: [],
          backgroundColor: "#2563eb",
          ...commonBar,
        },
      ],
    },
    options: opts(false, 5),
  });

  const revenueChart = new Chart(elRev, {
    type: "bar",
    data: {
      labels: [],
      datasets: [
        {
          label: "Pendapatan",
          data: [],
          backgroundColor: "#10b981",
          ...commonBar,
        },
      ],
    },
    options: opts(true, 100000),
  });

  const qtyChart = new Chart(elQty, {
    type: "bar",
    data: {
      labels: [],
      datasets: [
        {
          label: "Qty",
          data: [],
          backgroundColor: "#f59e0b",
          ...commonBar,
        },
      ],
    },
    options: opts(false, 5),
  });

  function apply(json) {
    // ambil dari API
    let labels = json.labels || [];
    let tx = (json.tx || []).map(Number);
    let rev = (json.revenue || []).map(Number);
    let qty = (json.qty || []).map(Number);

    // potong ke N hari terakhir
    const start = Math.max(0, labels.length - KEEP_DAYS);
    labels = labels.slice(start);
    tx = tx.slice(start);
    rev = rev.slice(start);
    qty = qty.slice(start);

    // tampilkan label ringkas
    const pretty = labels.map(fmtDay);

    // auto scale Y
    const maxTx = Math.max(1, ...tx);
    const maxRev = Math.max(1, ...rev);
    const maxQty = Math.max(1, ...qty);
    txChart.options.scales.y.suggestedMax = Math.ceil(maxTx * 1.25);
    revenueChart.options.scales.y.suggestedMax = Math.ceil(maxRev * 1.25);
    qtyChart.options.scales.y.suggestedMax = Math.ceil(maxQty * 1.25);

    // apply ke chart
    txChart.data.labels =
      revenueChart.data.labels =
      qtyChart.data.labels =
        pretty;
    txChart.data.datasets[0].data = tx;
    revenueChart.data.datasets[0].data = rev;
    qtyChart.data.datasets[0].data = qty;

    txChart.update();
    revenueChart.update();
    qtyChart.update();
  }

  async function load() {
    const res = await fetch(API, { cache: "no-store" });
    const json = await res.json();
    apply(json);
  }

  load();
  if (REFRESH_MS > 0) setInterval(load, REFRESH_MS);
});

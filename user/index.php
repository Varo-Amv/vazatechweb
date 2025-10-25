<?php include("./inc/headerlog.php")?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VAZATECH · Beranda</title>
  <!-- Font (opsional) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <!-- CSS -->
  <link rel="stylesheet" href="./assets/css/home.css">
</head>
<body>

  <main class="home">
    <!-- HERO -->
    <section class="hero container">
      <div class="hero-frame">
        <div class="hero-card skeleton"></div>
      </div>
      <ul class="hero-dots">
        <li class="dot"></li>
        <li class="dot active"></li>
        <li class="dot"></li>
      </ul>
    </section>

    <!-- FILTER CHIPS -->
    <section class="chips container">
      <button class="chip">Semua</button>
      <button class="chip">Mobile</button>
      <button class="chip">PC</button>
      <button class="chip">Voucher</button>
      <button class="chip">Promo</button>
    </section>

    <!-- TERPOPULER -->
    <section class="popular container">
      <h2 class="section-title">TERPOPULER</h2>

      <div class="cards">
        <article class="card">
          <div class="thumb skeleton"></div>
        </article>
        <article class="card">
          <div class="thumb skeleton"></div>
        </article>
        <article class="card">
          <div class="thumb skeleton"></div>
        </article>
        <article class="card">
          <div class="thumb skeleton"></div>
        </article>
      </div>
    </section>
  </main>

  <!-- (opsional) include footer -->
  <!-- <?php include __DIR__ . '/footer.php'; ?> -->
</body>
</html>

<?php include("./inc/footer.php")?>
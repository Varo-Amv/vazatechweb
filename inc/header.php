<?php 
session_start();
include_once("inc/koneksi.php");
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $_SESSION['user']['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$profileHref = '../profile.php'; // atau 'profile.php' sesuai routing kamu
$defaultAvatar = '../image/profile_white.png'; // sesuaikan path aset default-mu
$avatarPath = $user['avatar_path'] ?? '';

$avatarSrc = $avatarPath ? $avatarPath : $defaultAvatar;
?>


  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" sizes="32x32" href="../image/logo_nocapt.png" />
    <title>VAZATECH — Topup Game Murah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Lexend+Tera:wght@100..900&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="./assets/css/user.css" />
  </head>
  <body>
    <header>
      <!-- Logo di kiri (SESUAI BASE KAMU) -->
      <div class="logo">
        <img src="./image/logo_nocapt.png" alt="Logo" />
        <span class="logo">V A Z A T E C H</span>
      </div>

      <!-- Search di tengah -->
 <form id="searchForm" class="search" action="" method="get">
  <input
    class="search-input"
    type="search"
    name="q"
    placeholder="Cari produk / game / kategori…"
    value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>"
    aria-label="Cari produk"
  />
</form>

<script>
document.getElementById('searchForm').addEventListener('submit', function(e){
  e.preventDefault();
  const q = this.q.value.trim();
  if (!q) return;

  // Basis URL: pakai action kalau ada; jika relatif, jatuhkan ke path saat ini.
  const base = this.getAttribute('action') || (location.origin + location.pathname);
  const url  = new URL(base, location.origin);

  // TIMPA seluruh query string → hanya ada ?q=...
  url.search = 'q=' + encodeURIComponent(q);  // spasi → %20, bukan +

  // Arahkan
  location.href = url.toString();
});
</script>


      <!-- Aksi di kanan -->
      <nav class="actions">
        <a href="#" class="cart" aria-label="Keranjang">
          <!-- ikon cart -->
          <svg
            viewBox="0 0 24 24"
            width="26"
            height="26"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <circle cx="9" cy="20" r="1.5"></circle>
            <circle cx="18" cy="20" r="1.5"></circle>
            <path
              d="M1.5 1.5h3l2.4 12.5a2 2 0 0 0 2 1.6h9.9a2 2 0 0 0 2-1.6l1.6-8.5H6.2"
            ></path>
          </svg>
          <span class="badge" aria-hidden="true">0</span>
        </a>
        <?php
$loggedIn = !empty($_SESSION['user']['id']);               // set saat login
$isAdmin  = ($_SESSION['user']['role'] ?? '') === 'admin'; // role dari session
$profileHref = $isAdmin ? 'admin/index.php' : 'profile';

// Inisial untuk avatar teks (opsional, kalau mau pakai huruf)
$initials = '';
if ($loggedIn && $isAdmin): ?>
  <!-- Admin: pakai tombol seperti tombol login -->
  <a href="admin/index" class="btn btn-login" target="_blank">Admin Area</a>

<?php elseif ($loggedIn): ?>
  <!-- User biasa: tampilkan avatar -->
  <a href="<?= htmlspecialchars($profileHref) ?>" class="avatar-btn" title="Akun saya">
    <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profil" class="avatar-img" loading="lazy"
         onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatar) ?>'">
  </a>

<?php else: ?>
  <!-- Belum login -->
  <a href="./login" class="btn btn-login">Masuk</a>
<?php endif; ?>
      </nav>
    </header>
    <div class="topbar-accent" aria-hidden="true"></div>
<?php 
include_once("../inc/koneksi.php");
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $_SESSION['user']['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$profileHref = '../profile'; // atau 'profile.php' sesuai routing kamu
$defaultAvatar = '../image/profile_white.png'; // sesuaikan path aset default-mu
$avatarPath = $user['avatar_path'] ?? '';

$avatarSrc = $avatarPath ? $avatarPath : $defaultAvatar;
?>

    <link
      href="https://fonts.googleapis.com/css2?family=Lexend+Tera:wght@100..900&display=swap"
      rel="stylesheet"
    />
    <link rel="icon" type="image/png" sizes="32x22" href="https://ibb.co.com/wZj6z85S">
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
      integrity="sha384-7qAoOXltbVP82dhxHAUje59V5r2YsVfBafyUDxEdApLPmcdhBPg1DKg1ERo0BZlK"
      crossorigin="anonymous"
    ></script>

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
  </head>
  <body>
    <header>
      <!-- Logo di kiri -->
      <div class="logo">
        <img src="../image/logo_nocapt.png" alt="Logo" />
        <span class="logo">V A Z A T E C H</span>
      </div>
        <?php
$loggedIn = !empty($_SESSION['user']['id']);               // set saat login
$isAdmin  = ($_SESSION['user']['role'] ?? '') === 'admin'; // role dari session

// Inisial untuk avatar teks (opsional, kalau mau pakai huruf)
$initials = '';
if ($loggedIn): ?>
  <!-- User biasa: tampilkan avatar -->
  <a href="../profile" class="avatar-btn" title="Akun saya">
    <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profil" class="avatar-img" loading="lazy"
         onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatar) ?>'">
  </a>
<?php endif; ?>
      </nav>
    </header>
    <div class="topbar-accent" aria-hidden="true"></div>
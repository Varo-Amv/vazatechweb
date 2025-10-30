# TopUpWeb

Aplikasi web top-up / manajemen pesanan sederhana berbasis **PHP** (XAMPP) dengan struktur modular (admin, user, assets, API).

## Fitur Utama

- Autentikasi (login, logout, lupa/reset password, verifikasi).
- Halaman profil & edit profil + upload foto.
- Dashboard Admin: kelola blog, pesanan, stok, dan pengguna.
- Halaman User: blog, halaman informasi (S&K, Kebijakan Privasi, Affiliate).
- API sederhana (PHP) untuk data orders/stocks/users dan seri pendapatan.
- Integrasi **Composer** & **PHPMailer** (folder `vendor/`).

## Prasyarat

- **XAMPP** (Apache + PHP + MySQL).
- **Composer** (untuk `vendor/` jika belum tersedia).
- PHP 8.x direkomendasikan.

## Struktur Direktori

```
C:\xampp\htdocs\topupweb
├─ .htaccess
├─ 404.php
├─ composer.json
├─ composer.lock
├─ daftar.php
├─ forgot_password.php
├─ guard.txt
├─ index.php
├─ login.php
├─ logout.php
├─ php-error.log
├─ profile.php
├─ profile_edit.php
├─ reset_password.php
├─ test.php
├─ verifikasi.php
├─ admin/
│  ├─ banners.php
│  ├─ blog-create.php
│  ├─ blog-edit.php
│  ├─ blog-list.php
│  ├─ index.php
│  ├─ orders.php
│  ├─ stocks.php
│  └─ users.php
├─ assets/
│  ├─ api/
│  │  ├─ banners.php
│  │  ├─ orders.php
│  │  ├─ profile_upload.php
│  │  ├─ revenue_series.php
│  │  ├─ stocks.php
│  │  ├─ stocks_kpi.php
│  │  └─ users.php
│  ├─ css/
│  │  ├─ 404.css
│  │  ├─ blog-create.css
│  │  ├─ blog-edit.css
│  │  ├─ blog-list.css
│  │  ├─ blog.css
│  │  ├─ kbp.css
│  │  ├─ log.css
│  │  ├─ notify.css
│  │  ├─ promo.css
│  │  ├─ snk.css
│  │  ├─ verifikasi.css
│  │  ├─ admin.css
│  │  ├─ home.css
│  │  ├─ login.css
│  │  ├─ profile.css
│  │  └─ user.css
│  └─ js/
│     ├─ notify.js
│     ├─ chats.js
│     └─ revenue_chart.js
├─ blog/
│  └─ index.php
├─ image/
│  ├─ payments/
│  │  ├─ bca.png
│  │  ├─ dana.png
│  │  ├─ gopay.png
│  │  ├─ gopay_samping.png
│  │  ├─ jago.png
│  │  ├─ ovo.png
│  │  ├─ qris.png
│  │  ├─ seabank.png
│  │  └─ sopay.png
│  ├─ bgblue.jpg
│  ├─ centang_hijau.png
│  ├─ eyesvector.png
│  ├─ facebook.png
│  ├─ gmail.png
│  ├─ google.png
│  ├─ home_black.png
│  ├─ instagram.png
│  ├─ loginbg.jpg
│  ├─ loging.jpg
│  ├─ logo_nocapt.png
│  ├─ p.jpg
│  ├─ profile_black.png
│  ├─ profile_white.png
│  ├─ tiktok.png
│  └─ whatsapp.png
├─ inc/
│  ├─ auth.php
│  ├─ env.php
│  ├─ footer.php
│  ├─ fungsi.php
│  ├─ hdradmin.php
│  ├─ header.php
│  ├─ koneksi.php
│  └─ session.php
├─ user/
│  ├─ promo.php
│  ├─ refund-policy.php
│  ├─ blog.php
│  ├─ index.php
│  ├─ kebijakan-privasi.php
│  └─ snk.php
└─ vendor/
   ├─ autoload.php
   ├─ composer/
   │  ├─ autoload_classmap.php
   │  ├─ autoload_namespaces.php
   │  ├─ autoload_psr4.php
   │  ├─ autoload_real.php
   │  ├─ autoload_static.php
   │  ├─ ClassLoader.php
   │  ├─ installed.json
   │  ├─ installed.php
   │  ├─ InstalledVersions.php
   │  ├─ LICENSE
   │  └─ platform_check.php
   └─ phpmailer/
      └─ phpmailer/

```

## Konfigurasi & Menjalankan

1. Taruh folder `topupweb` di `C:\xampp\htdocs\`.
2. Jalankan **Apache** (dan **MySQL** jika dibutuhkan) dari XAMPP Control Panel.
3. Buka di browser: `http://localhost/topupweb/`.
4. **Database**: sesuaikan kredensial di `inc/koneksi.php`.
5. Jika folder `vendor/` belum ada atau kosong, jalankan:
   ```bash
   composer install
   ```
   Pastikan ekstensi `openssl` aktif di `php.ini` untuk Composer/PHPMailer.

## Rute/Entry File Penting

- `/index.php` — Beranda / landing.
- `/login.php`, `/logout.php`, `/daftar.php`, `/verifikasi.php`, `/forgot_password.php`, `/reset_password.php`.
- `/profile.php`, `/profile_edit.php`.
- `/admin/index.php` — dashboard admin.
- `/user/index.php` — halaman user.
- API: `/assets/api/*.php` (orders, users, stocks, revenue_series, profile_upload).

## Keamanan (Checklist Singkat)

- [ ] Filter & validasi semua input (GET/POST/FILES).
- [ ] Gunakan **prepared statements** untuk query DB (hindari SQL injection).
- [ ] Regenerasi session ID setelah login, atur `httponly` & `secure` untuk cookie.
- [ ] Batasi ukuran & tipe file upload di `profile_upload.php` (periksa MIME & ekstensi).
- [ ] Lindungi halaman admin dengan pengecekan role (lihat `inc/auth.php` / `inc/session.php`).
- [ ] Sembunyikan error di produksi (set `display_errors=Off`, log ke file).

## Lisensi

Internal project — sesuaikan per kebutuhan tim.

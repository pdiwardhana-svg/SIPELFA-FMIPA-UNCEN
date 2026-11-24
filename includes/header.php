<?php
/**
 * Pembuka halaman. Variabel yang bisa diatur di halaman sebelum file ini di-include:
 *   $judulHalaman  judul halaman
 *   $menuAktif     menu sidebar yang aktif
 *   $layout        'app' (dengan sidebar) atau 'auth' (login, registrasi, setup admin)
 */
$judulHalaman  = $judulHalaman ?? APP_NAME;
$menuAktif     = $menuAktif ?? '';
$layout        = $layout ?? 'app';
$penggunaAktif = $layout === 'app' ? user_saat_ini() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B1F3A">
    <title><?= e($judulHalaman) ?> - <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
    <link rel="alternate icon" href="<?= e(url('favicon.ico')) ?>">
    <link rel="apple-touch-icon" href="<?= e(url('assets/images/apple-touch-icon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="<?= $layout === 'auth' ? 'body-auth' : 'body-app' ?>">
<?php if ($layout === 'auth'): ?>
<div class="auth-wrap">
    <aside class="auth-aside">
        <a class="auth-logo" href="<?= e(url('login.php')) ?>">
            <img src="<?= e(url('assets/images/logo-sipelfa-putih.svg')) ?>" alt="SIPELFA" width="170" height="42">
        </a>
        <div class="auth-copy">
            <h1>Laporkan kerusakan fasilitas, pantau sampai selesai.</h1>
            <p>Sistem pelaporan kerusakan fasilitas, FMIPA Universitas Cenderawasih.</p>
        </div>
        <ol class="flow">
            <li><strong>Diajukan</strong><span>Laporan terkirim bersama foto dan lokasi kerusakan.</span></li>
            <li><strong>Diverifikasi</strong><span>Admin memeriksa laporan yang masuk.</span></li>
            <li><strong>Diproses</strong><span>Perbaikan sedang dikerjakan.</span></li>
            <li><strong>Selesai</strong><span>Fasilitas diperbaiki, lengkap dengan foto bukti.</span></li>
        </ol>
    </aside>
    <section class="auth-panel">
        <div class="auth-card">
            <a class="auth-card-logo" href="<?= e(url('login.php')) ?>">
                <img src="<?= e(url('assets/images/logo-sipelfa.svg')) ?>" alt="SIPELFA" width="140" height="35">
            </a>
<?php else: ?>
<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="app-main">
        <header class="topbar">
            <button type="button" class="menu-btn" data-sidebar-toggle aria-label="Buka menu"><?= icon('menu', 22) ?></button>
            <a class="topbar-logo" href="<?= e(url_dashboard((string) ($penggunaAktif['role'] ?? 'mahasiswa'))) ?>">
                <img src="<?= e(url('assets/images/logo-sipelfa.svg')) ?>" alt="SIPELFA" width="112" height="28">
            </a>
            <span class="topbar-context">FMIPA Universitas Cenderawasih</span>
            <div class="topbar-user">
                <span class="avatar" aria-hidden="true"><?= e(strtoupper(substr((string) ($penggunaAktif['nama_lengkap'] ?? '?'), 0, 1))) ?></span>
                <span class="topbar-name"><?= e($penggunaAktif['nama_lengkap'] ?? '') ?></span>
            </div>
        </header>
        <main class="content">
            <?php tampilkan_flash(); ?>
<?php endif; ?>

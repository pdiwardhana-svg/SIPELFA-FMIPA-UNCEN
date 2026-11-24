<?php
/** Sidebar navigasi. Menu berbeda untuk mahasiswa dan admin. */
if (($penggunaAktif['role'] ?? '') === 'admin') {
    $menu = [
        ['dashboard', 'Dashboard',        'admin/dashboard.php',        'dashboard'],
        ['laporan',   'Data laporan',     'admin/data_laporan.php',     'list'],
        ['mahasiswa', 'Data mahasiswa',   'admin/data_mahasiswa.php',   'users'],
        ['riwayat',   'Riwayat laporan',  'admin/riwayat_laporan.php',  'history'],
    ];
    $labelPeran = 'Admin';
} else {
    $menu = [
        ['dashboard', 'Dashboard',     'mahasiswa/dashboard.php',     'dashboard'],
        ['buat',      'Buat laporan',  'mahasiswa/buat_laporan.php',  'plus-file'],
        ['laporan',   'Laporan saya',  'mahasiswa/laporan_saya.php',  'list'],
    ];
    $labelPeran = 'Mahasiswa';
}
?>
<aside class="sidebar" id="sidebar" aria-label="Menu utama">
    <a class="sidebar-brand" href="<?= e(url_dashboard((string) $penggunaAktif['role'])) ?>">
        <img src="<?= e(url('assets/images/logo-sipelfa-putih.svg')) ?>" alt="SIPELFA" width="150" height="37">
    </a>

    <nav class="nav">
        <?php foreach ($menu as [$kunci, $label, $tujuan, $ikon]): ?>
            <a class="nav-link<?= $menuAktif === $kunci ? ' active' : '' ?>" href="<?= e(url($tujuan)) ?>"<?= $menuAktif === $kunci ? ' aria-current="page"' : '' ?>>
                <?= icon($ikon, 20) ?><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <span class="avatar" aria-hidden="true"><?= e(strtoupper(substr((string) $penggunaAktif['nama_lengkap'], 0, 1))) ?></span>
            <div>
                <div class="sidebar-user-name"><?= e($penggunaAktif['nama_lengkap']) ?></div>
                <div class="sidebar-user-meta"><?= e($labelPeran) ?>, <?= e($penggunaAktif['nim']) ?></div>
            </div>
        </div>
        <form method="post" action="<?= e(url('logout.php')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sidebar"><?= icon('logout', 18) ?><span>Keluar</span></button>
        </form>
    </div>
</aside>
<div class="sidebar-overlay" data-sidebar-close></div>

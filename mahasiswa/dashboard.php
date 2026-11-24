<?php
require_once __DIR__ . '/../includes/auth.php';

$user = wajib_role('mahasiswa');

$hitung = hitung_per_status((int) $user['id']);

$stmt = db()->prepare(
    'SELECT id, kategori_masalah, lokasi, status, tanggal_laporan
       FROM laporan WHERE user_id = ?
      ORDER BY tanggal_laporan DESC, id DESC LIMIT 5'
);
$stmt->execute([(int) $user['id']]);
$terbaru = $stmt->fetchAll();

$judulHalaman = 'Dashboard';
$menuAktif    = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h2>Halo, <?= e($user['nama_lengkap']) ?></h2>
        <p class="muted">Ringkasan laporan kerusakan fasilitas yang Anda ajukan.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('mahasiswa/buat_laporan.php')) ?>"><?= icon('plus-file', 18) ?>Buat laporan</a>
</div>

<div class="grid-stats">
    <?php
    kartu_statistik('Semua laporan', $hitung['Total'], 'file', 'netral');
    kartu_statistik('Diajukan', $hitung['Diajukan'], 'send', 'diajukan');
    kartu_statistik('Diproses', $hitung['Diproses'], 'wrench', 'diproses');
    kartu_statistik('Selesai', $hitung['Selesai'], 'done', 'selesai');
    ?>
</div>

<div class="card">
    <div class="card-head">
        <h3>Laporan terbaru</h3>
        <a class="btn btn-secondary btn-sm" href="<?= e(url('mahasiswa/laporan_saya.php')) ?>">Lihat semua</a>
    </div>
    <?php if (!$terbaru): ?>
        <div class="empty-state">
            <?= icon('file', 36) ?>
            <strong>Belum ada laporan</strong>
            <p>Temukan fasilitas yang rusak? Kirim laporan pertama Anda.</p>
            <a class="btn btn-primary" href="<?= e(url('mahasiswa/buat_laporan.php')) ?>">Buat laporan</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-stack">
                <thead>
                    <tr><th>Nomor</th><th>Kategori masalah</th><th>Lokasi</th><th>Tanggal</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($terbaru as $r): ?>
                    <tr>
                        <td data-label="Nomor" class="cell-strong nowrap"><?= e(nomor_laporan((int) $r['id'])) ?></td>
                        <td data-label="Kategori" class="cell-wrap"><?= e($r['kategori_masalah']) ?></td>
                        <td data-label="Lokasi" class="cell-wrap"><?= e($r['lokasi']) ?></td>
                        <td data-label="Tanggal" class="nowrap"><?= e(format_tanggal($r['tanggal_laporan'])) ?></td>
                        <td data-label="Status"><?= badge_status((string) $r['status']) ?></td>
                        <td data-label="Aksi" class="text-right"><a class="btn btn-secondary btn-sm" href="<?= e(url('mahasiswa/detail_laporan.php?id=' . (int) $r['id'])) ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';

$user = wajib_role('admin');

$hitung = hitung_per_status();
$jumlahMahasiswa = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();

$terbaru = db()->query(
    'SELECT l.id, l.kategori_masalah, l.lokasi, l.status, l.tanggal_laporan, u.nama_lengkap, u.nim
       FROM laporan l
       JOIN users u ON u.id = l.user_id
      ORDER BY l.tanggal_laporan DESC, l.id DESC
      LIMIT 8'
)->fetchAll();

$setupMasihAda = is_file(__DIR__ . '/../setup_admin.php');

$judulHalaman = 'Dashboard admin';
$menuAktif    = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h2>Dashboard admin</h2>
        <p class="muted">Pantau laporan kerusakan fasilitas yang masuk dari mahasiswa.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('admin/data_laporan.php')) ?>">Kelola laporan</a>
</div>

<?php if ($setupMasihAda): ?>
    <div class="alert alert-warning" role="status">
        <?= icon('alert', 18) ?>
        <span>File <strong>setup_admin.php</strong> masih ada di folder proyek. Hapus file tersebut demi keamanan.</span>
    </div>
<?php endif; ?>

<div class="grid-stats">
    <?php
    kartu_statistik('Total laporan', $hitung['Total'], 'file', 'netral');
    kartu_statistik('Diajukan', $hitung['Diajukan'], 'send', 'diajukan');
    kartu_statistik('Diverifikasi', $hitung['Diverifikasi'], 'verified', 'diverifikasi');
    kartu_statistik('Diproses', $hitung['Diproses'], 'wrench', 'diproses');
    kartu_statistik('Selesai', $hitung['Selesai'], 'done', 'selesai');
    kartu_statistik('Mahasiswa terdaftar', $jumlahMahasiswa, 'users', 'netral');
    ?>
</div>

<div class="card">
    <div class="card-head">
        <h3>Laporan terbaru</h3>
        <a class="btn btn-secondary btn-sm" href="<?= e(url('admin/data_laporan.php')) ?>">Lihat semua</a>
    </div>
    <?php if (!$terbaru): ?>
        <div class="empty-state">
            <?= icon('file', 36) ?>
            <strong>Belum ada laporan masuk</strong>
            <p>Laporan dari mahasiswa akan muncul di sini.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-stack">
                <thead>
                    <tr><th>Nomor</th><th>Mahasiswa</th><th>Kategori masalah</th><th>Lokasi</th><th>Tanggal</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($terbaru as $r): ?>
                    <tr>
                        <td data-label="Nomor" class="cell-strong nowrap"><?= e(nomor_laporan((int) $r['id'])) ?></td>
                        <td data-label="Mahasiswa"><?= e($r['nama_lengkap']) ?><span class="cell-sub"><?= e($r['nim']) ?></span></td>
                        <td data-label="Kategori" class="cell-wrap"><?= e($r['kategori_masalah']) ?></td>
                        <td data-label="Lokasi" class="cell-wrap"><?= e($r['lokasi']) ?></td>
                        <td data-label="Tanggal" class="nowrap"><?= e(format_tanggal($r['tanggal_laporan'])) ?></td>
                        <td data-label="Status"><?= badge_status((string) $r['status']) ?></td>
                        <td data-label="Aksi" class="text-right"><a class="btn btn-secondary btn-sm" href="<?= e(url('admin/detail_laporan.php?id=' . (int) $r['id'])) ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

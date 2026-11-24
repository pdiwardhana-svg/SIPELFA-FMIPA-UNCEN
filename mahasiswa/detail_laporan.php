<?php
require_once __DIR__ . '/../includes/auth.php';

$user = wajib_role('mahasiswa');
$id   = get_int('id');

// Syarat user_id memastikan mahasiswa tidak bisa membuka laporan milik orang lain.
$stmt = db()->prepare('SELECT * FROM laporan WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, (int) $user['id']]);
$laporan = $stmt->fetch();

if (!$laporan) {
    flash('error', 'Laporan tidak ditemukan.');
    redirect(url('mahasiswa/laporan_saya.php'));
}

$riwayat = ambil_riwayat((int) $laporan['id']);

// Kolom ini baru ada setelah SQL migrasi dijalankan; sebelum itu dianggap kosong.
$adaFotoPerbaikan = !empty($laporan['foto_perbaikan']);
$tanggalPerbaikan = $laporan['tanggal_perbaikan'] ?? null;

$judulHalaman = 'Detail laporan';
$menuAktif    = 'laporan';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h2>Laporan <?= e(nomor_laporan((int) $laporan['id'])) ?></h2>
        <p class="muted">Diajukan pada <?= e(format_tanggal($laporan['tanggal_laporan'])) ?></p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('mahasiswa/laporan_saya.php')) ?>">Kembali ke daftar</a>
</div>

<div class="detail-grid">
    <div>
        <div class="card">
            <div class="card-head"><h3>Informasi laporan</h3><?= badge_status((string) $laporan['status']) ?></div>
            <div class="card-body">
                <dl class="detail-list">
                    <div class="detail-split">
                        <div><dt>Kategori masalah</dt><dd><?= e($laporan['kategori_masalah']) ?></dd></div>
                        <div><dt>Lokasi kerusakan</dt><dd><?= e($laporan['lokasi']) ?></dd></div>
                        <?php if ($tanggalPerbaikan): ?>
                            <div><dt>Tanggal perbaikan</dt><dd><?= e(format_tanggal($tanggalPerbaikan, false)) ?></dd></div>
                        <?php endif; ?>
                    </div>
                    <div><dt>Deskripsi</dt><dd><?= nl2br(e($laporan['deskripsi'])) ?></dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Foto kerusakan</h3></div>
            <div class="card-body">
                <?php tampilkan_foto((int) $laporan['id'], 'kerusakan', 'Foto kerusakan: ' . $laporan['kategori_masalah'], !empty($laporan['foto']), 'Tidak ada foto pada laporan ini.'); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Foto bukti perbaikan</h3>
                <?php if ($tanggalPerbaikan): ?>
                    <span class="card-meta"><?= icon('calendar', 16) ?>Diperbaiki <?= e(format_tanggal($tanggalPerbaikan, false)) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php tampilkan_foto(
                    (int) $laporan['id'],
                    'perbaikan',
                    'Foto bukti perbaikan: ' . $laporan['kategori_masalah'],
                    $adaFotoPerbaikan,
                    $laporan['status'] === 'Selesai'
                        ? 'Foto bukti perbaikan belum tersedia untuk laporan ini.'
                        : 'Foto bukti perbaikan akan muncul di sini setelah fasilitas diperbaiki.'
                ); ?>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-head"><h3>Perkembangan laporan</h3></div>
            <div class="card-body">
                <?php tampilkan_stepper((string) $laporan['status']); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Riwayat status</h3></div>
            <div class="card-body">
                <?php tampilkan_timeline($riwayat); ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

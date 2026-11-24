<?php
require_once __DIR__ . '/../includes/auth.php';

$user = wajib_role('mahasiswa');

$q      = get_str('q');
$status = get_str('status');
if (!in_array($status, STATUS_LAPORAN, true)) {
    $status = '';
}

// Mahasiswa hanya melihat laporan miliknya sendiri (user_id selalu ikut di WHERE).
[$whereSql, $params] = filter_laporan($q, $status, false, (int) $user['id']);

$stmt = db()->prepare("SELECT COUNT(*) FROM laporan l $whereSql");
$stmt->execute($params);
$p = paginasi((int) $stmt->fetchColumn());

$stmt = db()->prepare(
    "SELECT l.id, l.kategori_masalah, l.lokasi, l.status, l.tanggal_laporan
       FROM laporan l $whereSql
      ORDER BY l.tanggal_laporan DESC, l.id DESC
      LIMIT {$p['per_halaman']} OFFSET {$p['offset']}"
);
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$judulHalaman = 'Laporan saya';
$menuAktif    = 'laporan';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h2>Laporan saya</h2>
        <p class="muted">Semua laporan yang Anda ajukan beserta status terbarunya.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('mahasiswa/buat_laporan.php')) ?>"><?= icon('plus-file', 18) ?>Buat laporan</a>
</div>

<form class="toolbar" method="get" action="">
    <div class="search-field">
        <?= icon('search', 18) ?>
        <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kategori, lokasi, atau nomor laporan" aria-label="Cari laporan">
    </div>
    <select class="input" name="status" aria-label="Filter status">
        <option value="">Semua status</option>
        <?php foreach (STATUS_LAPORAN as $s): ?>
            <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Terapkan</button>
    <?php if ($q !== '' || $status !== ''): ?>
        <a class="btn btn-ghost" href="<?= e(url('mahasiswa/laporan_saya.php')) ?>">Atur ulang</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if (!$daftar): ?>
        <div class="empty-state">
            <?= icon('file', 36) ?>
            <?php if ($q !== '' || $status !== ''): ?>
                <strong>Tidak ada laporan yang cocok</strong>
                <p>Ubah kata kunci atau filter status, lalu coba lagi.</p>
            <?php else: ?>
                <strong>Belum ada laporan</strong>
                <p>Laporan yang Anda kirim akan muncul di sini.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-stack">
                <thead>
                    <tr><th>Nomor</th><th>Kategori masalah</th><th>Lokasi</th><th>Tanggal</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($daftar as $r): ?>
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
        <?php tampilkan_paginasi($p); ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

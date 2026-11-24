<?php
require_once __DIR__ . '/../includes/auth.php';

$user = wajib_role('admin');

$q         = get_str('q');
$status    = get_str('status');
$laporanId = get_int('laporan');
if (!in_array($status, STATUS_LAPORAN, true)) {
    $status = '';
}

$where  = [];
$params = [];

if ($laporanId > 0) {
    $where[]  = 'r.laporan_id = ?';
    $params[] = $laporanId;
}
if ($status !== '') {
    $where[]  = 'r.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $like   = like_param($q);
    $bagian = [
        'l.kategori_masalah LIKE ?', 'l.lokasi LIKE ?', 'p.nama_lengkap LIKE ?',
        'p.nim LIKE ?', 'r.keterangan LIKE ?',
    ];
    for ($i = 0; $i < count($bagian); $i++) {
        $params[] = $like;
    }
    $idCari = id_dari_pencarian($q);
    if ($idCari !== null) {
        $bagian[] = 'r.laporan_id = ?';
        $params[] = $idCari;
    }
    $where[] = '(' . implode(' OR ', $bagian) . ')';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$dari = 'FROM riwayat_status r
         JOIN laporan l ON l.id = r.laporan_id
         JOIN users p ON p.id = l.user_id
         LEFT JOIN users a ON a.id = r.diubah_oleh';

$stmt = db()->prepare("SELECT COUNT(*) $dari $whereSql");
$stmt->execute($params);
$p = paginasi((int) $stmt->fetchColumn());

$stmt = db()->prepare(
    "SELECT r.id, r.laporan_id, r.status, r.keterangan, r.waktu_perubahan,
            l.kategori_masalah, p.nama_lengkap AS nama_pelapor, p.nim AS nim_pelapor,
            a.nama_lengkap AS nama_pengubah, a.role AS role_pengubah
       $dari
       $whereSql
      ORDER BY r.waktu_perubahan DESC, r.id DESC
      LIMIT {$p['per_halaman']} OFFSET {$p['offset']}"
);
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$judulHalaman = 'Riwayat laporan';
$menuAktif    = 'riwayat';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h2>Riwayat laporan</h2>
        <p class="muted">Seluruh perubahan status laporan, dari yang terbaru.<?= $laporanId > 0 ? ' Ditampilkan untuk laporan ' . e(nomor_laporan($laporanId)) . '.' : '' ?></p>
    </div>
    <?php if ($laporanId > 0): ?>
        <a class="btn btn-secondary" href="<?= e(url('admin/detail_laporan.php?id=' . $laporanId)) ?>">Buka laporan <?= e(nomor_laporan($laporanId)) ?></a>
    <?php endif; ?>
</div>

<form class="toolbar" method="get" action="">
    <?php if ($laporanId > 0): ?><input type="hidden" name="laporan" value="<?= $laporanId ?>"><?php endif; ?>
    <div class="search-field">
        <?= icon('search', 18) ?>
        <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nomor laporan, mahasiswa, kategori, atau keterangan" aria-label="Cari riwayat">
    </div>
    <select class="input" name="status" aria-label="Filter status">
        <option value="">Semua status</option>
        <?php foreach (STATUS_LAPORAN as $s): ?>
            <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Terapkan</button>
    <?php if ($q !== '' || $status !== '' || $laporanId > 0): ?>
        <a class="btn btn-ghost" href="<?= e(url('admin/riwayat_laporan.php')) ?>">Atur ulang</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if (!$daftar): ?>
        <div class="empty-state">
            <?= icon('history', 36) ?>
            <strong>Tidak ada riwayat yang cocok</strong>
            <p>Riwayat muncul setelah ada laporan dan perubahan status.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-stack">
                <thead>
                    <tr><th>Waktu</th><th>Laporan</th><th>Mahasiswa</th><th>Status</th><th>Keterangan</th><th>Diubah oleh</th></tr>
                </thead>
                <tbody>
                <?php foreach ($daftar as $r): ?>
                    <tr>
                        <td data-label="Waktu" class="nowrap"><?= e(format_tanggal($r['waktu_perubahan'])) ?></td>
                        <td data-label="Laporan" class="cell-wrap">
                            <a class="cell-strong" href="<?= e(url('admin/detail_laporan.php?id=' . (int) $r['laporan_id'])) ?>"><?= e(nomor_laporan((int) $r['laporan_id'])) ?></a>
                            <span class="cell-sub"><?= e($r['kategori_masalah']) ?></span>
                        </td>
                        <td data-label="Mahasiswa"><?= e($r['nama_pelapor']) ?><span class="cell-sub"><?= e($r['nim_pelapor']) ?></span></td>
                        <td data-label="Status"><?= badge_status((string) $r['status']) ?></td>
                        <td data-label="Keterangan" class="cell-wrap"><?= e($r['keterangan'] ?? '-') ?></td>
                        <td data-label="Diubah oleh"><?= e($r['nama_pengubah'] ?? '-') ?><span class="cell-sub"><?= ($r['role_pengubah'] ?? '') === 'admin' ? 'Admin' : 'Mahasiswa' ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php tampilkan_paginasi($p); ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

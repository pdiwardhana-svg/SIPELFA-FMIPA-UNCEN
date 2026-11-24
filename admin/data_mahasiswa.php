<?php
require_once __DIR__ . '/../includes/auth.php';

$admin = wajib_role('admin');

/* ---------- Aktifkan / nonaktifkan akun (hanya role mahasiswa) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi     = post_str('aksi');
    $targetId = (int) post_str('user_id');

    if (!csrf_valid()) {
        flash('error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!in_array($aksi, ['aktifkan', 'nonaktifkan'], true)) {
        flash('error', 'Aksi tidak dikenali.');
    } else {
        $stmt = db()->prepare("SELECT id, nama_lengkap FROM users WHERE id = ? AND role = 'mahasiswa' LIMIT 1");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'Akun mahasiswa tidak ditemukan.');
        } else {
            $statusAkun = $aksi === 'aktifkan' ? 'aktif' : 'nonaktif';
            $ubah = db()->prepare("UPDATE users SET status_akun = ? WHERE id = ? AND role = 'mahasiswa'");
            $ubah->execute([$statusAkun, (int) $target['id']]);
            flash('success', 'Akun ' . $target['nama_lengkap'] . ($aksi === 'aktifkan' ? ' diaktifkan kembali.' : ' dinonaktifkan. Laporan miliknya tetap tersimpan.'));
        }
    }
    redirect(query_url());
}

/* ---------- Daftar mahasiswa ---------- */
$q = get_str('q');
$statusAkunFilter = get_str('akun');
if (!in_array($statusAkunFilter, ['aktif', 'nonaktif'], true)) {
    $statusAkunFilter = '';
}

$where  = ["u.role = 'mahasiswa'"];
$params = [];
if ($q !== '') {
    $like     = like_param($q);
    $where[]  = '(u.nama_lengkap LIKE ? OR u.nim LIKE ?)';
    $params[] = $like;
    $params[] = $like;
}
if ($statusAkunFilter !== '') {
    $where[]  = 'u.status_akun = ?';
    $params[] = $statusAkunFilter;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = db()->prepare("SELECT COUNT(*) FROM users u $whereSql");
$stmt->execute($params);
$p = paginasi((int) $stmt->fetchColumn());

$stmt = db()->prepare(
    "SELECT u.id, u.nim, u.nama_lengkap, u.status_akun, u.created_at,
            (SELECT COUNT(*) FROM laporan l WHERE l.user_id = u.id) AS jumlah_laporan
       FROM users u
       $whereSql
      ORDER BY u.nama_lengkap ASC, u.id ASC
      LIMIT {$p['per_halaman']} OFFSET {$p['offset']}"
);
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$judulHalaman = 'Data mahasiswa';
$menuAktif    = 'mahasiswa';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h2>Data mahasiswa</h2>
        <p class="muted">Kelola akun mahasiswa. Password tidak dapat dilihat oleh siapa pun, termasuk admin.</p>
    </div>
</div>

<form class="toolbar" method="get" action="">
    <div class="search-field">
        <?= icon('search', 18) ?>
        <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau NIM" aria-label="Cari mahasiswa">
    </div>
    <select class="input" name="akun" aria-label="Filter status akun">
        <option value="">Semua akun</option>
        <option value="aktif"<?= $statusAkunFilter === 'aktif' ? ' selected' : '' ?>>Aktif</option>
        <option value="nonaktif"<?= $statusAkunFilter === 'nonaktif' ? ' selected' : '' ?>>Nonaktif</option>
    </select>
    <button type="submit" class="btn btn-primary">Terapkan</button>
    <?php if ($q !== '' || $statusAkunFilter !== ''): ?>
        <a class="btn btn-ghost" href="<?= e(url('admin/data_mahasiswa.php')) ?>">Atur ulang</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if (!$daftar): ?>
        <div class="empty-state">
            <?= icon('users', 36) ?>
            <strong><?= ($q !== '' || $statusAkunFilter !== '') ? 'Tidak ada mahasiswa yang cocok' : 'Belum ada mahasiswa terdaftar' ?></strong>
            <p><?= ($q !== '' || $statusAkunFilter !== '') ? 'Ubah kata kunci atau filter, lalu coba lagi.' : 'Mahasiswa mendaftar sendiri lewat halaman registrasi.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-stack">
                <thead>
                    <tr><th>Nama lengkap</th><th>NIM</th><th>Terdaftar</th><th>Laporan</th><th>Status akun</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($daftar as $m): ?>
                    <?php $aktif = $m['status_akun'] === 'aktif'; ?>
                    <tr>
                        <td data-label="Nama" class="cell-strong"><?= e($m['nama_lengkap']) ?></td>
                        <td data-label="NIM" class="nowrap"><?= e($m['nim']) ?></td>
                        <td data-label="Terdaftar" class="nowrap"><?= e(format_tanggal($m['created_at'], false)) ?></td>
                        <td data-label="Laporan"><?= (int) $m['jumlah_laporan'] ?></td>
                        <td data-label="Status akun"><?= badge_akun((string) $m['status_akun']) ?></td>
                        <td data-label="Aksi" class="text-right">
                            <form method="post" action="<?= e(query_url()) ?>"
                                  data-confirm="<?= e($aktif
                                      ? 'Akun ' . $m['nama_lengkap'] . ' akan dinonaktifkan dan tidak bisa login. Laporan miliknya tetap tersimpan.'
                                      : 'Akun ' . $m['nama_lengkap'] . ' akan diaktifkan kembali dan bisa login.') ?>"
                                  data-confirm-title="<?= $aktif ? 'Nonaktifkan akun' : 'Aktifkan akun' ?>"
                                  data-confirm-button="<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                  <?= $aktif ? 'data-confirm-danger="1"' : '' ?>>
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
                                <input type="hidden" name="aksi" value="<?= $aktif ? 'nonaktifkan' : 'aktifkan' ?>">
                                <button type="submit" class="btn btn-secondary btn-sm"><?= $aktif ? icon('ban', 16) . 'Nonaktifkan' : icon('check', 16) . 'Aktifkan' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php tampilkan_paginasi($p); ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';

$admin = wajib_role('admin'); // hanya admin yang boleh membuka halaman ini dan mengunggah foto perbaikan
$id    = get_int('id');

$stmt = db()->prepare(
    'SELECT l.*, u.nama_lengkap, u.nim
       FROM laporan l
       JOIN users u ON u.id = l.user_id
      WHERE l.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$laporan = $stmt->fetch();

if (!$laporan) {
    flash('error', 'Laporan tidak ditemukan.');
    redirect(url('admin/data_laporan.php'));
}

$fiturSiap  = fitur_perbaikan_siap();
$pesanMigrasi = 'Fitur foto bukti perbaikan belum aktif. Jalankan SQL migrasi di phpMyAdmin terlebih dahulu (lihat PANDUAN.md).';

$errors     = [];
$statusBaru = (string) $laporan['status'];
$keterangan = '';
$tglInput   = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = post_str('aksi');

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';

    /* ---------------- Unggah foto bukti perbaikan ---------------- */
    } elseif ($aksi === 'upload_perbaikan') {
        $tglInput = post_str('tanggal_perbaikan');
        $tgl      = DateTime::createFromFormat('Y-m-d', $tglInput);

        if (!$fiturSiap) {
            $errors[] = $pesanMigrasi;
        } elseif (!$tgl || $tgl->format('Y-m-d') !== $tglInput) {
            $errors[] = 'Tanggal perbaikan tidak valid.';
        } elseif ($tglInput > date('Y-m-d')) {
            $errors[] = 'Tanggal perbaikan tidak boleh di masa depan.';
        } else {
            $foto = simpan_foto_perbaikan($_FILES['foto_perbaikan'] ?? []);

            if (!$foto['ok']) {
                $errors[] = $foto['error'];
            } else {
                $pdo        = db();
                $fotoLama   = null;
                $tersimpan  = false;

                try {
                    $pdo->beginTransaction();

                    $cek = $pdo->prepare('SELECT status, foto_perbaikan FROM laporan WHERE id = ? FOR UPDATE');
                    $cek->execute([(int) $laporan['id']]);
                    $baris = $cek->fetch();
                    if (!$baris) {
                        throw new RuntimeException('Laporan tidak ditemukan.');
                    }
                    $fotoLama = $baris['foto_perbaikan'] ?? null;

                    // Hanya kolom foto_perbaikan dan tanggal_perbaikan yang berubah; foto kerusakan tidak disentuh.
                    $ubah = $pdo->prepare('UPDATE laporan SET foto_perbaikan = ?, tanggal_perbaikan = ? WHERE id = ?');
                    $ubah->execute([$foto['path'], $tglInput, (int) $laporan['id']]);

                    $riwayat = $pdo->prepare(
                        'INSERT INTO riwayat_status (laporan_id, diubah_oleh, status, keterangan, waktu_perubahan) VALUES (?, ?, ?, ?, ?)'
                    );
                    $riwayat->execute([
                        (int) $laporan['id'],
                        (int) $admin['id'],
                        (string) $baris['status'],
                        $fotoLama ? 'Foto bukti perbaikan diganti oleh admin.' : 'Foto bukti perbaikan diunggah oleh admin.',
                        date('Y-m-d H:i:s'),
                    ]);

                    $pdo->commit();
                    $tersimpan = true;
                } catch (Throwable $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    hapus_foto($foto['path'], 'perbaikan'); // jangan tinggalkan file sampah
                    error_log('[SIPELFA] Gagal menyimpan foto perbaikan: ' . $ex->getMessage());
                    $errors[] = 'Foto bukti perbaikan belum dapat disimpan. Silakan coba lagi.';
                }

                if ($tersimpan) {
                    if ($fotoLama) {
                        hapus_foto($fotoLama, 'perbaikan'); // hanya foto perbaikan lama; foto kerusakan aman
                    }
                    flash('success', 'Foto bukti perbaikan berhasil disimpan.');
                    redirect(url('admin/detail_laporan.php?id=' . (int) $laporan['id']));
                }
            }
        }

    /* ---------------- Ubah status laporan ---------------- */
    } else {
        $statusBaru = post_str('status');
        $keterangan = post_str('keterangan');

        if (!in_array($statusBaru, STATUS_LAPORAN, true)) {
            $errors[] = 'Status yang dipilih tidak valid.';
        } elseif (panjang($keterangan) > BATAS_KETERANGAN) {
            $errors[] = 'Keterangan maksimal ' . BATAS_KETERANGAN . ' karakter.';
        } else {
            $pdo      = db();
            $berhasil = false;

            try {
                $pdo->beginTransaction();

                // Kunci baris laporan agar perubahan status tidak bertabrakan.
                $cek = $pdo->prepare('SELECT ' . ($fiturSiap ? 'status, foto_perbaikan' : 'status') . ' FROM laporan WHERE id = ? FOR UPDATE');
                $cek->execute([(int) $laporan['id']]);
                $baris = $cek->fetch();

                if (!$baris) {
                    $pdo->rollBack();
                    $errors[] = 'Laporan tidak ditemukan.';
                } else {
                    $statusLama = (string) $baris['status'];

                    if ($statusBaru === 'Selesai' && $statusLama !== 'Selesai' && !$fiturSiap) {
                        $pdo->rollBack();
                        $errors[] = $pesanMigrasi;
                    } elseif ($statusBaru === 'Selesai' && $statusLama !== 'Selesai' && empty($baris['foto_perbaikan'])) {
                        $pdo->rollBack();
                        $errors[] = 'Unggah foto bukti perbaikan terlebih dahulu sebelum mengubah status menjadi Selesai.';
                    } elseif ($statusLama === $statusBaru && $keterangan === '') {
                        $pdo->rollBack();
                        $errors[] = 'Status tidak berubah. Pilih status lain, atau isi keterangan jika hanya ingin menambah catatan.';
                    } else {
                        $catatan = $keterangan !== ''
                            ? $keterangan
                            : 'Status diubah dari ' . $statusLama . ' menjadi ' . $statusBaru . '.';

                        $ubah = $pdo->prepare('UPDATE laporan SET status = ? WHERE id = ?');
                        $ubah->execute([$statusBaru, (int) $laporan['id']]);

                        $riwayat = $pdo->prepare(
                            'INSERT INTO riwayat_status (laporan_id, diubah_oleh, status, keterangan, waktu_perubahan) VALUES (?, ?, ?, ?, ?)'
                        );
                        $riwayat->execute([(int) $laporan['id'], (int) $admin['id'], $statusBaru, $catatan, date('Y-m-d H:i:s')]);

                        $pdo->commit();
                        $berhasil = true;
                    }
                }
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('[SIPELFA] Gagal mengubah status laporan: ' . $ex->getMessage());
                $errors[] = 'Status belum dapat diperbarui. Silakan coba lagi.';
            }

            if ($berhasil) {
                flash('success', 'Status laporan ' . nomor_laporan((int) $laporan['id']) . ' diperbarui menjadi ' . $statusBaru . '.');
                redirect(url('admin/detail_laporan.php?id=' . (int) $laporan['id']));
            }
        }
    }
}

$riwayat          = ambil_riwayat((int) $laporan['id']);
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
    <a class="btn btn-secondary" href="<?= e(url('admin/data_laporan.php')) ?>">Kembali ke daftar</a>
</div>

<?php tampilkan_errors($errors); ?>

<?php if (!$fiturSiap): ?>
    <div class="alert alert-warning" role="status">
        <?= icon('alert', 18) ?>
        <span><?= e($pesanMigrasi) ?></span>
    </div>
<?php endif; ?>

<div class="detail-grid">
    <div>
        <div class="card">
            <div class="card-head"><h3>Informasi laporan</h3><?= badge_status((string) $laporan['status']) ?></div>
            <div class="card-body">
                <dl class="detail-list">
                    <div class="detail-split">
                        <div><dt>Nama mahasiswa</dt><dd><?= e($laporan['nama_lengkap']) ?></dd></div>
                        <div><dt>NIM</dt><dd><?= e($laporan['nim']) ?></dd></div>
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
            <div class="card-head"><h3>Foto kerusakan</h3><span class="card-meta">Diunggah mahasiswa</span></div>
            <div class="card-body">
                <?php tampilkan_foto((int) $laporan['id'], 'kerusakan', 'Foto kerusakan: ' . $laporan['kategori_masalah'], !empty($laporan['foto']), 'Tidak ada foto pada laporan ini.'); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Foto bukti perbaikan</h3>
                <?php if ($tanggalPerbaikan): ?>
                    <span class="card-meta"><?= icon('calendar', 16) ?>Diperbaiki <?= e(format_tanggal($tanggalPerbaikan, false)) ?></span>
                <?php else: ?>
                    <span class="card-meta">Diunggah admin</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php tampilkan_foto((int) $laporan['id'], 'perbaikan', 'Foto bukti perbaikan: ' . $laporan['kategori_masalah'], $adaFotoPerbaikan, 'Belum ada foto bukti perbaikan.'); ?>

                <?php if ($fiturSiap): ?>
                    <form class="upload-form" method="post" action="" enctype="multipart/form-data" data-once="Mengunggah..." novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="upload_perbaikan">

                        <div class="form-group">
                            <label for="foto_perbaikan"><?= $adaFotoPerbaikan ? 'Ganti foto bukti perbaikan' : 'Unggah foto bukti perbaikan' ?></label>
                            <div class="file-field">
                                <input type="file" id="foto_perbaikan" name="foto_perbaikan" accept="image/jpeg,image/png,image/webp"
                                       data-preview="perbaikan-pratinjau" data-preview-msg="perbaikan-pesan" data-max-bytes="<?= MAKS_UKURAN_FOTO ?>" required>
                                <p class="file-error" id="perbaikan-pesan" role="alert"></p>
                                <div class="file-preview" id="perbaikan-pratinjau"></div>
                            </div>
                            <p class="hint">JPG, PNG, atau WEBP, maksimal 5 MB. Foto kerusakan dari mahasiswa tidak berubah.</p>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_perbaikan">Tanggal perbaikan</label>
                            <input class="input input-date" type="date" id="tanggal_perbaikan" name="tanggal_perbaikan"
                                   value="<?= e($tglInput) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary"><?= icon('upload', 18) ?>Simpan foto perbaikan</button>
                    </form>
                <?php endif; ?>
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
            <div class="card-head"><h3>Ubah status</h3></div>
            <div class="card-body">
                <form method="post" action="" data-confirm="Status laporan akan diubah dan tercatat di riwayat. Lanjutkan?" data-confirm-title="Ubah status laporan" data-confirm-button="Ubah status" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="ubah_status">
                    <div class="form-group">
                        <label for="status">Status baru</label>
                        <select class="input" id="status" name="status">
                            <?php foreach (STATUS_LAPORAN as $s): ?>
                                <option value="<?= e($s) ?>"<?= $statusBaru === $s ? ' selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="hint<?= ($fiturSiap && !$adaFotoPerbaikan) ? ' hint-warn' : '' ?>">
                            <?= ($fiturSiap && !$adaFotoPerbaikan)
                                ? 'Status Selesai hanya bisa dipilih setelah foto bukti perbaikan diunggah.'
                                : 'Status Selesai memerlukan foto bukti perbaikan.' ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan perubahan</label>
                        <textarea class="input" id="keterangan" name="keterangan" maxlength="<?= BATAS_KETERANGAN ?>" placeholder="Contoh: Teknisi dijadwalkan datang Senin pagi."><?= e($keterangan) ?></textarea>
                        <p class="hint">Opsional. Keterangan ini bisa dibaca mahasiswa pelapor.</p>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Riwayat status</h3><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/riwayat_laporan.php?laporan=' . (int) $laporan['id'])) ?>">Lihat di riwayat laporan</a></div>
            <div class="card-body">
                <?php tampilkan_timeline($riwayat); ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';

$user = wajib_role('mahasiswa');

$errors    = [];
$kategori  = '';
$lokasi    = '';
$deskripsi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori  = post_str('kategori_masalah');
    $lokasi    = post_str('lokasi');
    $deskripsi = post_str('deskripsi');

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        if ($kategori === '') {
            $errors[] = 'Kategori masalah wajib diisi.';
        } elseif (panjang($kategori) > BATAS_KATEGORI) {
            $errors[] = 'Kategori masalah maksimal ' . BATAS_KATEGORI . ' karakter.';
        }

        if ($lokasi === '') {
            $errors[] = 'Lokasi kerusakan wajib diisi.';
        } elseif (panjang($lokasi) > BATAS_LOKASI) {
            $errors[] = 'Lokasi kerusakan maksimal ' . BATAS_LOKASI . ' karakter.';
        }

        if (panjang($deskripsi) < 10) {
            $errors[] = 'Deskripsi kerusakan minimal 10 karakter.';
        } elseif (panjang($deskripsi) > BATAS_DESKRIPSI) {
            $errors[] = 'Deskripsi kerusakan maksimal ' . BATAS_DESKRIPSI . ' karakter.';
        }

        if (!$errors) {
            // 1) Simpan foto lebih dulu. Jika gagal, laporan tidak disimpan.
            $foto = simpan_foto_laporan($_FILES['foto'] ?? []);
            if (!$foto['ok']) {
                $errors[] = $foto['error'];
            } else {
                // 2) Simpan laporan dan riwayat awal dalam satu transaksi.
                $pdo       = db();
                $sekarang  = date('Y-m-d H:i:s');
                $laporanId = 0;

                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare(
                        "INSERT INTO laporan (user_id, kategori_masalah, lokasi, deskripsi, foto, status, tanggal_laporan)
                         VALUES (?, ?, ?, ?, ?, 'Diajukan', ?)"
                    );
                    $stmt->execute([(int) $user['id'], $kategori, $lokasi, $deskripsi, $foto['path'], $sekarang]);
                    $laporanId = (int) $pdo->lastInsertId();

                    $stmt = $pdo->prepare(
                        "INSERT INTO riwayat_status (laporan_id, diubah_oleh, status, keterangan, waktu_perubahan)
                         VALUES (?, ?, 'Diajukan', ?, ?)"
                    );
                    $stmt->execute([$laporanId, (int) $user['id'], 'Laporan diajukan oleh mahasiswa.', $sekarang]);

                    $pdo->commit();
                } catch (Throwable $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    hapus_foto($foto['path']); // jangan tinggalkan file sampah
                    error_log('[SIPELFA] Gagal menyimpan laporan: ' . $ex->getMessage());
                    $laporanId = 0;
                    $errors[]  = 'Laporan belum dapat disimpan. Silakan coba lagi.';
                }

                if ($laporanId > 0) {
                    flash('success', 'Laporan berhasil dikirim. Nomor laporan Anda: ' . nomor_laporan($laporanId) . '.');
                    redirect(url('mahasiswa/detail_laporan.php?id=' . $laporanId));
                }
            }
        }
    }
}

$judulHalaman = 'Buat laporan';
$menuAktif    = 'buat';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h2>Buat laporan kerusakan</h2>
        <p class="muted">Jelaskan kerusakan fasilitas dan lampirkan fotonya.</p>
    </div>
</div>

<?php tampilkan_errors($errors); ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="" enctype="multipart/form-data" data-once="Mengirim laporan..." novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="kategori_masalah">Kategori masalah</label>
                <input class="input" type="text" id="kategori_masalah" name="kategori_masalah" value="<?= e($kategori) ?>"
                       maxlength="<?= BATAS_KATEGORI ?>" placeholder="Contoh: AC rusak, kursi patah, kaca jendela pecah" autocomplete="off" required>
                <p class="hint">Ketik sendiri jenis kerusakannya.</p>
            </div>

            <div class="form-group">
                <label for="lokasi">Lokasi kerusakan</label>
                <input class="input" type="text" id="lokasi" name="lokasi" value="<?= e($lokasi) ?>"
                       maxlength="<?= BATAS_LOKASI ?>" placeholder="Contoh: Ruang kuliah 2, lantai 1, sisi jendela" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi kerusakan</label>
                <textarea class="input" id="deskripsi" name="deskripsi" maxlength="<?= BATAS_DESKRIPSI ?>"
                          placeholder="Ceritakan kondisi kerusakan, sejak kapan terjadi, dan dampaknya bagi kegiatan perkuliahan." required><?= e($deskripsi) ?></textarea>
            </div>

            <div class="form-group">
                <label for="foto">Foto kerusakan</label>
                <div class="file-field">
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp"
                           data-preview="foto-pratinjau" data-preview-msg="foto-pesan" data-max-bytes="<?= MAKS_UKURAN_FOTO ?>" required>
                    <p class="file-error" id="foto-pesan" role="alert"></p>
                    <div class="file-preview" id="foto-pratinjau"></div>
                </div>
                <p class="hint">Format JPG, PNG, atau WEBP. Ukuran maksimal 5 MB. Jika formulir gagal dikirim, pilih foto sekali lagi.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= icon('send', 18) ?>Kirim laporan</button>
                <a class="btn btn-ghost" href="<?= e(url('mahasiswa/dashboard.php')) ?>">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

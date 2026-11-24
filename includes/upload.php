<?php
/**
 * Penanganan upload foto.
 *   - Foto kerusakan (mahasiswa)  -> uploads/laporan/
 *   - Foto bukti perbaikan (admin) -> uploads/perbaikan/
 * Foto selalu bernama acak dan hanya ditampilkan lewat foto.php (yang memeriksa hak akses).
 */

require_once __DIR__ . '/functions.php';

/** $jenis: 'laporan' (foto kerusakan) atau 'perbaikan' (foto bukti perbaikan). */
function folder_upload(string $jenis): string
{
    return dirname(__DIR__) . '/uploads/' . ($jenis === 'perbaikan' ? 'perbaikan' : 'laporan');
}

function folder_upload_laporan(): string
{
    return folder_upload('laporan');
}

/**
 * Memvalidasi dan menyimpan satu foto.
 * Mengembalikan ['ok' => bool, 'path' => 'uploads/<jenis>/xxx.jpg'|null, 'error' => string|null].
 */
function proses_upload_foto(array $berkas, string $jenis, string $pesanWajib): array
{
    $jenis = $jenis === 'perbaikan' ? 'perbaikan' : 'laporan';
    $gagal = function (string $pesan): array {
        return ['ok' => false, 'path' => null, 'error' => $pesan];
    };

    if (!isset($berkas['error'], $berkas['tmp_name'], $berkas['name'], $berkas['size']) || is_array($berkas['error'])) {
        return $gagal($pesanWajib);
    }

    switch ($berkas['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return $gagal($pesanWajib);
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return $gagal('Ukuran foto terlalu besar. Maksimal 5 MB.');
        default:
            return $gagal('Foto gagal diunggah. Silakan coba lagi.');
    }

    if ($berkas['size'] > MAKS_UKURAN_FOTO) {
        return $gagal('Ukuran foto terlalu besar. Maksimal 5 MB.');
    }

    if (!is_uploaded_file($berkas['tmp_name'])) {
        return $gagal('Berkas foto tidak valid. Silakan pilih foto lagi.');
    }

    // Ekstensi dan jenis (MIME) harus sama-sama gambar yang diizinkan.
    $izinMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $izinEkst = ['jpg', 'jpeg', 'png', 'webp'];

    $ekstensi = strtolower(pathinfo((string) $berkas['name'], PATHINFO_EXTENSION));
    if (!in_array($ekstensi, $izinEkst, true)) {
        return $gagal('Format foto harus JPG, PNG, atau WEBP.');
    }

    $info = @getimagesize($berkas['tmp_name']);
    if ($info === false || !isset($info['mime'], $izinMime[$info['mime']])) {
        return $gagal('Berkas bukan foto yang valid. Gunakan JPG, PNG, atau WEBP.');
    }
    $mime = $info['mime'];

    if (function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeAsli = $finfo ? finfo_file($finfo, $berkas['tmp_name']) : $mime;
        if ($mimeAsli !== $mime) {
            return $gagal('Berkas bukan foto yang valid. Gunakan JPG, PNG, atau WEBP.');
        }
    }

    $folder = folder_upload($jenis);
    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        error_log('[SIPELFA] Folder upload tidak dapat dibuat: ' . $folder);
        return $gagal('Foto belum dapat disimpan di server. Hubungi admin.');
    }

    // Nama acak; ekstensi ditentukan dari isi file, bukan dari nama aslinya.
    $namaBaru = bin2hex(random_bytes(16)) . '.' . $izinMime[$mime];
    if (!move_uploaded_file($berkas['tmp_name'], $folder . '/' . $namaBaru)) {
        error_log('[SIPELFA] move_uploaded_file gagal ke ' . $folder);
        return $gagal('Foto gagal disimpan. Silakan coba lagi.');
    }

    return ['ok' => true, 'path' => 'uploads/' . $jenis . '/' . $namaBaru, 'error' => null];
}

/** Foto kerusakan oleh mahasiswa (fungsi lama, perilaku tidak berubah). */
function simpan_foto_laporan(array $berkas): array
{
    return proses_upload_foto($berkas, 'laporan', 'Foto kerusakan wajib diunggah.');
}

/** Foto bukti perbaikan oleh admin. */
function simpan_foto_perbaikan(array $berkas): array
{
    return proses_upload_foto($berkas, 'perbaikan', 'Pilih foto bukti perbaikan terlebih dahulu.');
}

/** Menghapus foto dari disk (dipakai jika penyimpanan database gagal atau foto diganti). */
function hapus_foto(?string $pathRelatif, string $jenis = 'laporan'): void
{
    if (!$pathRelatif) {
        return;
    }
    $file = folder_upload($jenis) . '/' . basename($pathRelatif);
    if (is_file($file)) {
        @unlink($file);
    }
}

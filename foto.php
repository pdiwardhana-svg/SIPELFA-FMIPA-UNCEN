<?php
/**
 * Menampilkan foto setelah memeriksa hak akses:
 * admin boleh melihat semua foto, mahasiswa hanya foto laporannya sendiri.
 *   foto.php?id=12                    foto kerusakan (default, tidak berubah dari versi lama)
 *   foto.php?id=12&jenis=perbaikan    foto bukti perbaikan
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';

$user  = wajib_login();
$id    = get_int('id');
$jenis = get_str('jenis') === 'perbaikan' ? 'perbaikan' : 'laporan';

$baris = false;
if ($jenis === 'laporan') {
    $stmt = db()->prepare('SELECT user_id, foto AS berkas FROM laporan WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $baris = $stmt->fetch();
} elseif (fitur_perbaikan_siap()) {
    $stmt = db()->prepare('SELECT user_id, foto_perbaikan AS berkas FROM laporan WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $baris = $stmt->fetch();
}

$boleh = $baris && ($user['role'] === 'admin' || (int) $baris['user_id'] === (int) $user['id']);

$file = '';
if ($boleh) {
    $nama = basename((string) $baris['berkas']);
    $file = $nama !== '' ? folder_upload($jenis) . '/' . $nama : '';
}

$info = ($file !== '' && is_file($file)) ? @getimagesize($file) : false;
$izin = ['image/jpeg', 'image/png', 'image/webp'];

if (!$info || !in_array($info['mime'], $izin, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Foto tidak ditemukan.');
}

session_write_close();
while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: ' . $info['mime']);
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($file);
exit;

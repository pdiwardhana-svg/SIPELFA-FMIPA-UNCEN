<?php
/**
 * Komponen tampilan yang dipakai bersama oleh banyak halaman.
 */

require_once __DIR__ . '/functions.php';

/** Ikon SVG sebaris (tanpa perlu internet). */
function icon(string $nama, int $ukuran = 20): string
{
    static $path = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'plus-file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M12 12v6M9 15h6"/>',
        'list'      => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6M9 16h4"/>',
        'file'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'history'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'check'     => '<path d="M5 12l5 5L20 7"/>',
        'x'         => '<path d="M6 6l12 12M18 6L6 18"/>',
        'alert'     => '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'image'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/>',
        'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'building'  => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 21v-4h6v4"/>',
        'wrench'    => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'verified'  => '<path d="M12 3l2.5 2 3.2-.3.9 3.1 2.7 1.8-1.2 3 1.2 3-2.7 1.8-.9 3.1-3.2-.3L12 21l-2.5-2-3.2.3-.9-3.1-2.7-1.8 1.2-3-1.2-3 2.7-1.8.9-3.1 3.2.3z"/><path d="M9 12l2 2 4-4"/>',
        'done'      => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'send'      => '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>',
        'ban'       => '<circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>',
        'camera'    => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'upload'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/>',
        'zoom'      => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/>',
    ];

    $isi = $path[$nama] ?? '';
    return '<svg class="icon" width="' . $ukuran . '" height="' . $ukuran . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $isi . '</svg>';
}

function tampilkan_alert(string $tipe, string $pesan): void
{
    if (!in_array($tipe, ['success', 'error', 'warning', 'info'], true)) {
        $tipe = 'info';
    }
    $ikon = $tipe === 'success' ? 'check' : ($tipe === 'info' ? 'info' : 'alert');
    echo '<div class="alert alert-' . $tipe . '" role="alert">' . icon($ikon, 18)
        . '<span>' . e($pesan) . '</span>'
        . '<button type="button" class="alert-close" aria-label="Tutup pemberitahuan">' . icon('x', 16) . '</button></div>';
}

function tampilkan_flash(): void
{
    foreach (ambil_flash() as $f) {
        tampilkan_alert((string) ($f['type'] ?? 'info'), (string) ($f['message'] ?? ''));
    }
}

function tampilkan_errors(array $errors): void
{
    if (!$errors) {
        return;
    }
    echo '<div class="alert alert-error" role="alert">' . icon('alert', 18) . '<div>';
    if (count($errors) === 1) {
        echo '<span>' . e($errors[0]) . '</span>';
    } else {
        echo '<span>Periksa kembali isian Anda:</span><ul>';
        foreach ($errors as $pesan) {
            echo '<li>' . e($pesan) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div><button type="button" class="alert-close" aria-label="Tutup pemberitahuan">' . icon('x', 16) . '</button></div>';
}

function kartu_statistik(string $label, $nilai, string $ikon, string $nada = 'netral'): void
{
    echo '<div class="stat-card stat-' . e($nada) . '"><span class="stat-icon">' . icon($ikon, 22) . '</span>'
        . '<div><div class="stat-value">' . e((string) $nilai) . '</div><div class="stat-label">' . e($label) . '</div></div></div>';
}

function tampilkan_paginasi(array $p): void
{
    if ($p['total'] > 0) {
        $dari   = $p['offset'] + 1;
        $sampai = min($p['offset'] + $p['per_halaman'], $p['total']);
        echo '<p class="table-info">Menampilkan ' . $dari . ' sampai ' . $sampai . ' dari ' . $p['total'] . ' data</p>';
    }
    if ($p['total_halaman'] <= 1) {
        return;
    }

    $h = $p['halaman'];
    $t = $p['total_halaman'];

    $tautan = function (int $no, string $teks, bool $aktif = false, bool $mati = false): string {
        if ($mati) {
            return '<span class="page-link disabled">' . $teks . '</span>';
        }
        return '<a class="page-link' . ($aktif ? ' active' : '') . '" href="' . e(query_url(['page' => $no])) . '"'
            . ($aktif ? ' aria-current="page"' : '') . '>' . $teks . '</a>';
    };

    echo '<nav class="pagination" aria-label="Navigasi halaman">';
    echo $tautan($h - 1, 'Sebelumnya', false, $h <= 1);
    for ($i = max(1, $h - 2); $i <= min($t, $h + 2); $i++) {
        echo $tautan($i, (string) $i, $i === $h);
    }
    echo $tautan($h + 1, 'Berikutnya', false, $h >= $t);
    echo '</nav>';
}

/** Alur status laporan: Diajukan, Diverifikasi, Diproses, Selesai. */
function tampilkan_stepper(string $statusSekarang): void
{
    $indeks = array_search($statusSekarang, STATUS_LAPORAN, true);
    if ($indeks === false) {
        $indeks = 0;
    }

    echo '<ol class="stepper">';
    foreach (STATUS_LAPORAN as $i => $nama) {
        $selesai = ($i === $indeks && $nama === 'Selesai');
        $kelas   = ($i < $indeks || $selesai) ? 'done' : ($i === $indeks ? 'current' : '');
        echo '<li class="step ' . $kelas . '"' . ($i === $indeks ? ' aria-current="step"' : '') . '>'
            . '<span class="step-dot">' . ($kelas === 'done' ? icon('check', 14) : '') . '</span>'
            . '<span class="step-name">' . e($nama) . '</span></li>';
    }
    echo '</ol>';
}

/** Riwayat perubahan status (terbaru di atas). */
function tampilkan_timeline(array $baris): void
{
    if (!$baris) {
        echo '<p class="muted">Belum ada riwayat status.</p>';
        return;
    }

    echo '<ol class="timeline">';
    foreach ($baris as $r) {
        $peran = ($r['role_pengubah'] ?? '') === 'admin' ? 'Admin' : 'Mahasiswa';
        $nama  = $r['nama_pengubah'] ?? 'Pengguna';
        echo '<li class="timeline-item"><span class="timeline-marker"></span><div class="timeline-body">';
        echo '<div class="timeline-head">' . badge_status((string) $r['status']) . '<time>' . e(format_tanggal($r['waktu_perubahan'])) . '</time></div>';
        if (!empty($r['keterangan'])) {
            echo '<p>' . nl2br(e($r['keterangan'])) . '</p>';
        }
        echo '<div class="timeline-meta">' . e($peran) . ': ' . e($nama) . '</div>';
        echo '</div></li>';
    }
    echo '</ol>';
}

/** Foto laporan (jenis 'kerusakan' atau 'perbaikan') dengan zoom, atau pesan jika belum ada. */
function tampilkan_foto(int $laporanId, string $jenis, string $alt, bool $ada, string $pesanKosong): void
{
    if (!$ada) {
        echo '<div class="photo-missing">' . icon('image', 32) . '<p>' . e($pesanKosong) . '</p></div>';
        return;
    }
    $src = url('foto.php?id=' . $laporanId . ($jenis === 'perbaikan' ? '&jenis=perbaikan' : ''));
    echo '<div class="photo-box"><img src="' . e($src) . '" data-zoom="' . e($src) . '" alt="' . e($alt) . '" loading="lazy"></div>';
    echo '<div class="photo-tools"><span>Klik foto untuk memperbesar</span><a href="' . e($src) . '" target="_blank" rel="noopener">Buka ukuran penuh</a></div>';
}

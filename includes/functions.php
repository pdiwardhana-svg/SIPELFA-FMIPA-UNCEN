<?php
/**
 * Fungsi umum SIPELFA FMIPA UNCEN.
 * Dimuat oleh semua halaman melalui includes/auth.php.
 */

require_once __DIR__ . '/../config/database.php';

/* ------------------------------------------------------------------
 * Pengaturan aplikasi
 * ------------------------------------------------------------------ */
const APP_NAME  = 'SIPELFA FMIPA UNCEN';
const APP_DEBUG = false; // ubah ke true HANYA saat mencari kesalahan di komputer sendiri

const STATUS_LAPORAN   = ['Diajukan', 'Diverifikasi', 'Diproses', 'Selesai'];
const MAKS_UKURAN_FOTO = 5242880; // 5 MB
const PER_HALAMAN      = 10;

// Batas panjang input. Sesuaikan dengan panjang kolom di database jika perlu
// (lihat PANDUAN.md bagian "Memeriksa struktur tabel").
const BATAS_NAMA       = 100;
const BATAS_KATEGORI   = 100;
const BATAS_LOKASI     = 100;
const BATAS_DESKRIPSI  = 2000;
const BATAS_KETERANGAN = 255;
const MIN_PASSWORD     = 8;
const MAKS_PASSWORD    = 72;

date_default_timezone_set('Asia/Jayapura');
ob_start();

/* ------------------------------------------------------------------
 * Penanganan error: pengguna hanya melihat pesan umum.
 * Detail teknis dicatat di log Apache (xampp/apache/logs/error.log).
 * ------------------------------------------------------------------ */
set_exception_handler(function ($e) {
    error_log('[SIPELFA] ' . $e);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Terjadi kesalahan</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#f1f4f5;color:#16262c;display:grid;place-items:center;min-height:100vh;margin:0}'
        . 'main{max-width:480px;padding:32px;background:#fff;border:1px solid #dbe4e7;border-radius:12px}h1{font-size:20px;margin:0 0 8px}p{margin:0 0 12px;line-height:1.55}a{color:#1b6b7a}</style></head>'
        . '<body><main><h1>Terjadi kesalahan pada sistem</h1><p>Permintaan Anda belum dapat diproses. Silakan coba lagi beberapa saat lagi.</p>';
    if (APP_DEBUG) {
        echo '<pre style="white-space:pre-wrap;font-size:12px">' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '<p><a href="javascript:history.back()">Kembali</a></p></main></body></html>';
});

/* ------------------------------------------------------------------
 * Session
 * ------------------------------------------------------------------ */
function mulai_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('SIPELFASESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ------------------------------------------------------------------
 * Alamat (URL) dan pengalihan
 * ------------------------------------------------------------------ */

/** Mendeteksi folder proyek relatif terhadap htdocs, misalnya "/sipelfa-fmipa". */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $base    = '';
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $root    = realpath(__DIR__ . '/..');
    if ($docRoot && $root) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $root    = rtrim(str_replace('\\', '/', $root), '/');
        if (stripos($root, $docRoot) === 0) {
            $base = (string) substr($root, strlen($docRoot));
        }
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function redirect(string $tujuan): void
{
    header('Location: ' . $tujuan);
    exit;
}

/** Alamat halaman saat ini dengan parameter GET yang bisa diubah (untuk filter/pagination). */
function query_url(array $ubah = []): string
{
    $q = array_merge($_GET, $ubah);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    $qs = http_build_query($q);
    return ($_SERVER['SCRIPT_NAME'] ?? '') . ($qs !== '' ? '?' . $qs : '');
}

/* ------------------------------------------------------------------
 * Output aman, notifikasi, CSRF
 * ------------------------------------------------------------------ */
function e($nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = ['type' => $tipe, 'message' => $pesan];
}

function ambil_flash(): array
{
    $daftar = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($daftar) ? $daftar : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $kirim = $_POST['csrf_token'] ?? '';
    return is_string($kirim) && $kirim !== '' && hash_equals(csrf_token(), $kirim);
}

/* ------------------------------------------------------------------
 * Input
 * ------------------------------------------------------------------ */
function post_str(string $kunci): string
{
    $v = $_POST[$kunci] ?? '';
    return is_string($v) ? trim($v) : '';
}

function get_str(string $kunci): string
{
    $v = $_GET[$kunci] ?? '';
    return is_string($v) ? trim($v) : '';
}

function get_int(string $kunci, int $bawaan = 0): int
{
    $v = filter_var($_GET[$kunci] ?? null, FILTER_VALIDATE_INT);
    return ($v === false || $v === null) ? $bawaan : (int) $v;
}

function panjang(string $teks): int
{
    return function_exists('mb_strlen') ? mb_strlen($teks, 'UTF-8') : strlen($teks);
}

/* ------------------------------------------------------------------
 * Tampilan data
 * ------------------------------------------------------------------ */
function format_tanggal(?string $waktu, bool $denganJam = true): string
{
    if (!$waktu) {
        return '-';
    }
    try {
        $dt = new DateTime($waktu, new DateTimeZone('Asia/Jayapura'));
    } catch (Exception $ex) {
        return $waktu;
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $teks  = $dt->format('j') . ' ' . $bulan[(int) $dt->format('n')] . ' ' . $dt->format('Y');
    if ($denganJam) {
        $teks .= ', ' . $dt->format('H.i') . ' WIT';
    }
    return $teks;
}

function nomor_laporan(int $id): string
{
    return 'LP-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function badge_status(string $status): string
{
    $kelas = [
        'Diajukan'     => 'status-diajukan',
        'Diverifikasi' => 'status-diverifikasi',
        'Diproses'     => 'status-diproses',
        'Selesai'      => 'status-selesai',
    ][$status] ?? 'status-diajukan';
    return '<span class="badge ' . $kelas . '">' . e($status) . '</span>';
}

function badge_akun(string $status): string
{
    $aktif = $status === 'aktif';
    return '<span class="badge ' . ($aktif ? 'akun-aktif' : 'akun-nonaktif') . '">' . ($aktif ? 'Aktif' : 'Nonaktif') . '</span>';
}

/* ------------------------------------------------------------------
 * Pagination dan pencarian
 * ------------------------------------------------------------------ */
function paginasi(int $total, int $perHalaman = PER_HALAMAN): array
{
    $totalHalaman = max(1, (int) ceil($total / $perHalaman));
    $halaman      = max(1, min($totalHalaman, get_int('page', 1)));
    return [
        'total'         => $total,
        'per_halaman'   => $perHalaman,
        'total_halaman' => $totalHalaman,
        'halaman'       => $halaman,
        'offset'        => ($halaman - 1) * $perHalaman,
    ];
}

/** Menyiapkan kata kunci untuk LIKE (karakter % dan _ dianggap huruf biasa). */
function like_param(string $teks): string
{
    return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $teks) . '%';
}

/** Mengenali pencarian berupa nomor laporan, misalnya "LP-0012" atau "12". */
function id_dari_pencarian(string $q): ?int
{
    if (preg_match('/^(?:LP-?)?0*(\d{1,9})$/i', $q, $cocok)) {
        return (int) $cocok[1];
    }
    return null;
}

/**
 * Menyusun klausa WHERE untuk daftar laporan (alias tabel: l = laporan, u = users).
 * Mengembalikan [string_where, parameter].
 */
function filter_laporan(string $q, string $status, bool $cariPelapor = false, ?int $userId = null): array
{
    $where  = [];
    $params = [];

    if ($userId !== null) {
        $where[]  = 'l.user_id = ?';
        $params[] = $userId;
    }

    if ($q !== '') {
        $like  = like_param($q);
        $kolom = ['l.kategori_masalah', 'l.lokasi', 'l.deskripsi'];
        if ($cariPelapor) {
            $kolom[] = 'u.nama_lengkap';
            $kolom[] = 'u.nim';
        }
        $bagian = [];
        foreach ($kolom as $k) {
            $bagian[] = $k . ' LIKE ?';
            $params[] = $like;
        }
        $id = id_dari_pencarian($q);
        if ($id !== null) {
            $bagian[] = 'l.id = ?';
            $params[] = $id;
        }
        $where[] = '(' . implode(' OR ', $bagian) . ')';
    }

    if ($status !== '' && in_array($status, STATUS_LAPORAN, true)) {
        $where[]  = 'l.status = ?';
        $params[] = $status;
    }

    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

/* ------------------------------------------------------------------
 * Query yang dipakai berulang
 * ------------------------------------------------------------------ */

/** Jumlah laporan per status. Kunci: Diajukan, Diverifikasi, Diproses, Selesai, Total. */
function hitung_per_status(?int $userId = null): array
{
    $hasil  = array_fill_keys(STATUS_LAPORAN, 0);
    $sql    = 'SELECT status, COUNT(*) AS jumlah FROM laporan';
    $params = [];
    if ($userId !== null) {
        $sql     .= ' WHERE user_id = ?';
        $params[] = $userId;
    }
    $sql .= ' GROUP BY status';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $baris) {
        if (isset($hasil[$baris['status']])) {
            $hasil[$baris['status']] = (int) $baris['jumlah'];
        }
    }
    $hasil['Total'] = array_sum($hasil);
    return $hasil;
}

function ambil_riwayat(int $laporanId): array
{
    $stmt = db()->prepare(
        'SELECT r.status, r.keterangan, r.waktu_perubahan, u.nama_lengkap AS nama_pengubah, u.role AS role_pengubah
           FROM riwayat_status r
           LEFT JOIN users u ON u.id = r.diubah_oleh
          WHERE r.laporan_id = ?
          ORDER BY r.waktu_perubahan DESC, r.id DESC'
    );
    $stmt->execute([$laporanId]);
    return $stmt->fetchAll();
}

function ada_admin(): bool
{
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
}

function nim_sudah_terpakai(string $nim): bool
{
    $stmt = db()->prepare('SELECT 1 FROM users WHERE nim = ? LIMIT 1');
    $stmt->execute([$nim]);
    return (bool) $stmt->fetchColumn();
}

/** Validasi isian akun baru (dipakai registrasi mahasiswa dan setup admin). */
function validasi_akun_baru(string $nama, string $nim, string $password, string $konfirmasi): array
{
    $errors = [];

    if (panjang($nama) < 3) {
        $errors[] = 'Nama lengkap minimal 3 karakter.';
    } elseif (panjang($nama) > BATAS_NAMA) {
        $errors[] = 'Nama lengkap maksimal ' . BATAS_NAMA . ' karakter.';
    }

    if ($nim === '') {
        $errors[] = 'NIM wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9]{3,30}$/', $nim)) {
        $errors[] = 'NIM hanya boleh berisi huruf dan angka, 3 sampai 30 karakter.';
    }

    if (strlen($password) < MIN_PASSWORD) {
        $errors[] = 'Password minimal ' . MIN_PASSWORD . ' karakter.';
    } elseif (strlen($password) > MAKS_PASSWORD) {
        $errors[] = 'Password maksimal ' . MAKS_PASSWORD . ' karakter.';
    }

    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    return $errors;
}

/** Menyimpan akun baru ke tabel users. Password disimpan dengan password_hash(). */
function buat_akun(string $nim, string $nama, string $password, string $role): void
{
    $stmt = db()->prepare(
        'INSERT INTO users (nim, nama_lengkap, password_hash, role, status_akun, created_at) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$nim, $nama, password_hash($password, PASSWORD_DEFAULT), $role, 'aktif', date('Y-m-d H:i:s')]);
}

/**
 * Apakah kolom foto_perbaikan dan tanggal_perbaikan sudah ada di tabel laporan?
 * Dipakai agar website tidak error jika SQL migrasi belum dijalankan.
 */
function fitur_perbaikan_siap(): bool
{
    static $siap = null;
    if ($siap !== null) {
        return $siap;
    }
    $stmt = db()->query("SHOW COLUMNS FROM laporan WHERE Field IN ('foto_perbaikan', 'tanggal_perbaikan')");
    $siap = count($stmt->fetchAll()) === 2;
    return $siap;
}

mulai_session();

<?php
/**
 * Autentikasi dan pemeriksaan hak akses (dijalankan di sisi server pada setiap halaman).
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/components.php';

/** Data pengguna yang sedang login (selalu diambil dari database, tanpa password_hash). */
function user_saat_ini(bool $muatUlang = false): ?array
{
    static $user = null;
    static $dimuat = false;

    if ($muatUlang) {
        $dimuat = false;
    }
    if ($dimuat) {
        return $user;
    }

    $dimuat = true;
    $user   = null;
    $id     = $_SESSION['user_id'] ?? null;

    if ($id) {
        $stmt = db()->prepare('SELECT id, nim, nama_lengkap, role, status_akun FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $id]);
        $baris = $stmt->fetch();
        $user  = $baris ?: null;
    }

    return $user;
}

function url_dashboard(string $role): string
{
    return $role === 'admin' ? url('admin/dashboard.php') : url('mahasiswa/dashboard.php');
}

/** Memulai sesi login. ID session diganti baru untuk mencegah session fixation. */
function masuk_sesi(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['csrf']);
    user_saat_ini(true);
}

function keluar_sesi(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    user_saat_ini(true);
}

/** Halaman yang hanya boleh dibuka setelah login. Akun nonaktif otomatis dikeluarkan. */
function wajib_login(): array
{
    $user = user_saat_ini();

    if (!$user) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect(url('login.php'));
    }

    if ($user['status_akun'] !== 'aktif' || !in_array($user['role'], ['mahasiswa', 'admin'], true)) {
        keluar_sesi();
        mulai_session();
        flash('error', 'Akun Anda sedang dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.');
        redirect(url('login.php'));
    }

    return $user;
}

/** Halaman yang hanya boleh dibuka oleh role tertentu ('mahasiswa' atau 'admin'). */
function wajib_role(string $role): array
{
    $user = wajib_login();

    if ($user['role'] !== $role) {
        flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect(url_dashboard($user['role']));
    }

    return $user;
}

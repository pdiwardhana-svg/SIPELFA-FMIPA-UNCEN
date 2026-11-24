<?php
require_once __DIR__ . '/includes/auth.php';

$sekarang = user_saat_ini();
if ($sekarang && $sekarang['status_akun'] === 'aktif') {
    redirect(url_dashboard($sekarang['role']));
}

$errors = [];
$nim    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim      = post_str('nim');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $kunci    = (int) ($_SESSION['login_kunci_sampai'] ?? 0);

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($kunci > time()) {
        $errors[] = 'Terlalu banyak percobaan login. Coba lagi dalam ' . ($kunci - time()) . ' detik.';
    } elseif ($nim === '' || $password === '') {
        $errors[] = 'NIM dan password wajib diisi.';
    } else {
        $stmt = db()->prepare('SELECT id, nim, nama_lengkap, password_hash, role, status_akun FROM users WHERE nim = ? LIMIT 1');
        $stmt->execute([$nim]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $gagal = (int) ($_SESSION['login_gagal'] ?? 0) + 1;
            $_SESSION['login_gagal'] = $gagal;
            if ($gagal >= 5) {
                $_SESSION['login_kunci_sampai'] = time() + 60;
                $_SESSION['login_gagal']        = 0;
            }
            $errors[] = 'NIM atau password salah.';
        } elseif ($user['status_akun'] !== 'aktif') {
            $errors[] = 'Akun Anda sedang dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.';
        } else {
            unset($_SESSION['login_gagal'], $_SESSION['login_kunci_sampai']);
            masuk_sesi($user);
            redirect(url_dashboard($user['role']));
        }
    }
}

$adaAdmin      = ada_admin();
$setupMasihAda = is_file(__DIR__ . '/setup_admin.php');

$judulHalaman = 'Masuk';
$layout       = 'auth';
include __DIR__ . '/includes/header.php';
?>
<h2>Masuk ke akun Anda</h2>
<p class="auth-sub">Gunakan NIM dan password yang terdaftar.</p>

<?php tampilkan_flash(); ?>
<?php tampilkan_errors($errors); ?>

<?php if (!$adaAdmin): ?>
    <div class="alert alert-info" role="status">
        <?= icon('info', 18) ?>
        <span>Belum ada akun admin. Pemilik website dapat membuatnya di <a href="<?= e(url('setup_admin.php')) ?>">halaman setup admin</a>.</span>
    </div>
<?php elseif ($setupMasihAda): ?>
    <div class="alert alert-warning" role="status">
        <?= icon('alert', 18) ?>
        <span>File <strong>setup_admin.php</strong> masih ada di server. Hapus file tersebut demi keamanan.</span>
    </div>
<?php endif; ?>

<form method="post" action="" novalidate>
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="nim">NIM</label>
        <input class="input" type="text" id="nim" name="nim" value="<?= e($nim) ?>" maxlength="30" autocomplete="username" autofocus required>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <div class="pw-wrap">
            <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Tampilkan password"><?= icon('eye', 18) ?></button>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Masuk</button>
</form>

<p class="auth-foot">Mahasiswa belum punya akun? <a href="<?= e(url('register.php')) ?>">Daftar di sini</a></p>
<?php include __DIR__ . '/includes/footer.php'; ?>

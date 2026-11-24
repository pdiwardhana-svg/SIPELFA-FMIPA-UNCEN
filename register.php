<?php
require_once __DIR__ . '/includes/auth.php';

$sekarang = user_saat_ini();
if ($sekarang && $sekarang['status_akun'] === 'aktif') {
    redirect(url_dashboard($sekarang['role']));
}

$errors = [];
$nama   = '';
$nim    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = post_str('nama_lengkap');
    $nim        = post_str('nim');
    $password   = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $konfirmasi = is_string($_POST['konfirmasi_password'] ?? null) ? $_POST['konfirmasi_password'] : '';

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $errors = validasi_akun_baru($nama, $nim, $password, $konfirmasi);

        if (!$errors && nim_sudah_terpakai($nim)) {
            $errors[] = 'NIM sudah terdaftar. Silakan login atau hubungi admin.';
        }

        if (!$errors) {
            try {
                // Role selalu 'mahasiswa'; pengguna tidak bisa memilih role sendiri.
                buat_akun($nim, $nama, $password, 'mahasiswa');
                flash('success', 'Pendaftaran berhasil. Silakan login dengan NIM dan password Anda.');
                redirect(url('login.php'));
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $errors[] = 'NIM sudah terdaftar. Silakan login atau hubungi admin.';
                } else {
                    throw $ex;
                }
            }
        }
    }
}

$judulHalaman = 'Daftar';
$layout       = 'auth';
include __DIR__ . '/includes/header.php';
?>
<h2>Daftar akun mahasiswa</h2>
<p class="auth-sub">Akun dipakai untuk mengirim dan memantau laporan kerusakan.</p>

<?php tampilkan_errors($errors); ?>

<form method="post" action="" novalidate>
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="nama_lengkap">Nama lengkap</label>
        <input class="input" type="text" id="nama_lengkap" name="nama_lengkap" value="<?= e($nama) ?>" maxlength="<?= BATAS_NAMA ?>" autocomplete="name" required>
    </div>
    <div class="form-group">
        <label for="nim">NIM</label>
        <input class="input" type="text" id="nim" name="nim" value="<?= e($nim) ?>" maxlength="30" autocomplete="username" required>
        <p class="hint">Huruf dan angka saja, tanpa spasi.</p>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <div class="pw-wrap">
            <input class="input" type="password" id="password" name="password" minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password" required>
            <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Tampilkan password"><?= icon('eye', 18) ?></button>
        </div>
        <p class="hint">Minimal <?= MIN_PASSWORD ?> karakter.</p>
    </div>
    <div class="form-group">
        <label for="konfirmasi_password">Konfirmasi password</label>
        <div class="pw-wrap">
            <input class="input" type="password" id="konfirmasi_password" name="konfirmasi_password" autocomplete="new-password" required>
            <button type="button" class="pw-toggle" data-toggle-password="konfirmasi_password" aria-label="Tampilkan password"><?= icon('eye', 18) ?></button>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Buat akun</button>
</form>

<p class="auth-foot">Sudah punya akun? <a href="<?= e(url('login.php')) ?>">Masuk</a></p>
<?php include __DIR__ . '/includes/footer.php'; ?>

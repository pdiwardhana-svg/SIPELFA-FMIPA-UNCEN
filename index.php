<?php
require_once __DIR__ . '/includes/auth.php';

$user = user_saat_ini();
if ($user && $user['status_akun'] === 'aktif') {
    redirect(url_dashboard($user['role']));
}
redirect(url('login.php'));

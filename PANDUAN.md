# SIPELFA FMIPA UNCEN, versi 2

# Bagian A. Memperbarui website yang sudah berjalan

## Yang baru di versi 2

1. **Foto bukti perbaikan.** Admin mengunggah foto perbaikan di halaman detail laporan (dengan pratinjau dan tanggal perbaikan). Foto disimpan terpisah di `uploads/perbaikan/`. Foto kerusakan dari mahasiswa tidak disentuh.
2. **Status Selesai wajib punya foto bukti.** Admin tidak bisa mengubah status ke Selesai sebelum foto perbaikan diunggah.
3. **Mahasiswa melihat dua foto** (kerusakan dan perbaikan) beserta tanggal perbaikan di detail laporannya. Hanya foto miliknya sendiri yang bisa dibuka.
4. **Redesign total**: navy dengan aksen kuning keemasan, sidebar dan navbar baru, kartu, tabel, formulir, dan notifikasi yang seragam, responsif untuk laptop, tablet, dan HP.
5. **Logo SIPELFA baru** (SVG) dan favicon, terpasang di login, registrasi, navbar, sidebar, dan tab browser.

Semua logika lama (login, registrasi, laporan, status, riwayat, akun mahasiswa, pembatasan akses) tidak diubah. `login.php`, `register.php`, `logout.php`, `setup_admin.php`, `buat_laporan.php`, `laporan_saya.php`, dashboard, `data_laporan.php`, `data_mahasiswa.php`, `riwayat_laporan.php`, `config/database.php`, dan `includes/auth.php` tidak diubah sama sekali. Mereka hanya tampil dengan gaya baru lewat CSS, header, dan sidebar.

## Langkah memperbarui (ikuti urutannya)

**Langkah 1. Cadangkan dulu (wajib).**

- Database: phpMyAdmin > klik `db_sipelfa_fmipa` > tab **Export** > **Export** (format SQL). Simpan file hasilnya.
- Folder proyek: salin `C:\xampp\htdocs\sipelfa-fmipa` menjadi `sipelfa-fmipa-cadangan` di tempat lain.

**Langkah 2. Jalankan SQL migrasi (satu kali).**

phpMyAdmin > klik `db_sipelfa_fmipa` > tab **SQL** > tempel isi file `database/migrasi_foto_perbaikan.sql` (atau isi di bawah) > **Go**.

```sql
ALTER TABLE laporan
  ADD COLUMN foto_perbaikan VARCHAR(255) NULL DEFAULT NULL AFTER foto,
  ADD COLUMN tanggal_perbaikan DATE NULL DEFAULT NULL AFTER foto_perbaikan;
```

Fungsi setiap perubahan:

| Perubahan | Fungsi |
|---|---|
| `foto_perbaikan` | Menyimpan lokasi foto bukti perbaikan, misalnya `uploads/perbaikan/ab12....jpg`. Kolom `foto` (foto kerusakan) tidak diubah. |
| `tanggal_perbaikan` | Menyimpan tanggal fasilitas diperbaiki (diisi admin). |

Keamanan untuk data lama: perintah ini hanya **menambah** dua kolom ke tabel `laporan`. Tidak ada tabel atau kolom yang dihapus, diubah namanya, atau dikosongkan. Semua laporan lama tetap utuh dan kolom barunya kosong (NULL). Jalankan sekali saja; jika dijalankan dua kali, MySQL menolak dengan pesan "Duplicate column name" dan tidak ada yang rusak.

Untuk memeriksa hasilnya: tab SQL, jalankan `SHOW COLUMNS FROM laporan;` dan pastikan `foto_perbaikan` dan `tanggal_perbaikan` muncul.

**Langkah 3. Salin file versi 2.**

Ekstrak ZIP, lalu salin isinya ke `C:\xampp\htdocs\sipelfa-fmipa` dan pilih **Replace** (timpa) jika ditanya.

- **Jangan menghapus folder `uploads/`.** Isinya adalah foto kerusakan yang sudah dikirim mahasiswa. Menimpa file saja sudah cukup; ZIP ini tidak berisi foto apa pun.
- ZIP ini **tidak menyertakan `setup_admin.php`**, karena file itu seharusnya sudah Anda hapus. Jangan dikembalikan.
- Jika Anda pernah mengedit file proyek sendiri setelah versi 1, bandingkan dulu dengan cadangan. File yang berubah di versi 2 ada di daftar berikut, dan hanya file inilah yang akan menimpa perubahan Anda:

```
DIUBAH   admin/detail_laporan.php        upload foto perbaikan, aturan status Selesai
DIUBAH   mahasiswa/detail_laporan.php    menampilkan foto dan tanggal perbaikan
DIUBAH   foto.php                        mendukung ?jenis=perbaikan (cek hak akses tetap)
DIUBAH   includes/upload.php             upload dipakai bersama; fungsi lama tetap ada
DIUBAH   includes/functions.php          + fitur_perbaikan_siap()
DIUBAH   includes/components.php         + ikon baru, tampilan foto
DIUBAH   includes/header.php             logo, favicon, navbar baru
DIUBAH   includes/sidebar.php            logo dan sidebar baru
DIUBAH   assets/css/style.css            redesign total
DIUBAH   assets/js/app.js                pratinjau foto untuk dua formulir
BARU     assets/images/logo-sipelfa.svg  logo (latar terang)
BARU     assets/images/logo-sipelfa-putih.svg  logo (latar navy)
BARU     assets/images/logo-mark.svg     tanda logo saja
BARU     assets/images/favicon.svg       favicon
BARU     assets/images/apple-touch-icon.png
BARU     favicon.ico
BARU     uploads/perbaikan/.htaccess     folder foto perbaikan (dilindungi)
BARU     database/migrasi_foto_perbaikan.sql  (+ .htaccess)
```

**Langkah 4.** Buka website lalu tekan **Ctrl + F5** agar browser memuat CSS dan logo baru.

Urutan langkah 2 dan 3 boleh tertukar. Jika file baru sudah disalin tetapi SQL belum dijalankan, website tetap berjalan normal; admin hanya melihat pemberitahuan bahwa fitur foto perbaikan belum aktif, dan status Selesai belum bisa dipilih.

## Cara memakai fitur foto bukti perbaikan

1. Login sebagai admin > **Data laporan** > **Detail** pada laporan yang sudah diperbaiki.
2. Pada kartu **Foto bukti perbaikan**, pilih foto (pratinjau muncul), atur tanggal perbaikan, klik **Simpan foto perbaikan**.
3. Pada **Ubah status**, pilih **Selesai** dan simpan.
4. Login sebagai mahasiswa pemilik laporan: detail laporan menampilkan foto kerusakan, foto bukti perbaikan, dan tanggal perbaikan.

Laporan lama yang sudah berstatus Selesai sebelum versi 2 tidak punya foto perbaikan. Admin dapat menambahkannya kapan saja dari halaman detail.

## Daftar uji manual versi 2

Kode versi 2 diperiksa secara statis (kelengkapan file, keseimbangan kode, path), dan tampilannya diperiksa lewat pratinjau browser pada lebar laptop, tablet, dan HP. **Kode PHP belum dijalankan** karena lingkungan penulisnya tidak memiliki PHP dan MySQL. Karena itu semua poin di bawah perlu Anda uji di XAMPP.

Fitur baru:

- [ ] 1. SQL migrasi berhasil; `SHOW COLUMNS FROM laporan;` menampilkan dua kolom baru
- [ ] 2. Sebelum SQL dijalankan (jika mau dicoba di salinan): admin melihat pemberitahuan dan status Selesai ditolak
- [ ] 3. Admin memilih foto perbaikan dan pratinjau muncul sebelum disimpan
- [ ] 4. Foto perbaikan tersimpan; file muncul di `uploads/perbaikan/` dengan nama acak
- [ ] 5. File `.php`, atau file lain yang diganti ekstensi jadi `.jpg`, ditolak; foto di atas 5 MB ditolak dengan pesan jelas
- [ ] 6. Foto kerusakan asli tetap tampil dan tetap ada di `uploads/laporan/`
- [ ] 7. Mengubah status ke Selesai tanpa foto perbaikan ditolak dengan pesan jelas
- [ ] 8. Setelah foto perbaikan diunggah, status Selesai berhasil dan riwayat bertambah
- [ ] 9. Mahasiswa pemilik laporan melihat foto kerusakan, foto perbaikan, dan tanggal perbaikan
- [ ] 10. Mahasiswa lain tidak bisa membuka laporan itu, dan tidak bisa membuka `foto.php?id=...&jenis=perbaikan` milik orang lain (ubah angka `id`)
- [ ] 11. Mahasiswa tidak melihat formulir upload perbaikan, dan tidak bisa membuka `admin/detail_laporan.php`
- [ ] 12. Mengganti foto perbaikan: foto baru tampil, foto perbaikan lama terhapus dari folder, foto kerusakan tetap ada

Fitur lama (pastikan tidak rusak):

- [ ] 13. Login admin dan mahasiswa dengan NIM; registrasi mahasiswa; logout
- [ ] 14. Mahasiswa mengirim laporan (kategori diketik bebas) dengan foto
- [ ] 15. Admin mengubah status ke Diverifikasi dan Diproses; riwayat tersimpan
- [ ] 16. Aktivasi dan penonaktifan akun mahasiswa; akun nonaktif ditolak saat login
- [ ] 17. Halaman admin tidak bisa dibuka mahasiswa; semua halaman tertutup tanpa login
- [ ] 18. Data laporan, mahasiswa, dan riwayat lama masih lengkap

Tampilan:

- [ ] 19. Logo dan favicon tampil di login, registrasi, sidebar, navbar, dan tab browser
- [ ] 20. Laptop: sidebar tetap di kiri. HP: sidebar tersembunyi dan terbuka lewat tombol menu
- [ ] 21. HP: tabel tampil bertumpuk, foto tidak melebar, halaman tidak bergeser ke samping

---

# Bagian B. Panduan dasar instalasi (dari versi 1)

Sistem Informasi Pelaporan Kerusakan Fasilitas FMIPA Universitas Cenderawasih, studi kasus Gedung Fisika.
Dibuat dengan HTML, CSS, JavaScript, PHP Native, dan MySQL. Berjalan di XAMPP.

---

## 1. Menyalin proyek ke XAMPP

1. Ekstrak file ZIP. Anda akan mendapat folder bernama `sipelfa-fmipa`.
2. Salin folder itu ke `C:\xampp\htdocs\`.
3. Hasil akhirnya: `C:\xampp\htdocs\sipelfa-fmipa\login.php` (dan file lainnya).

Folder boleh diberi nama lain. Alamat website akan ikut menyesuaikan.

## 2. Menjalankan Apache dan MySQL

1. Buka **XAMPP Control Panel**.
2. Klik **Start** pada baris **Apache**.
3. Klik **Start** pada baris **MySQL**.
4. Tunggu sampai nama modul berwarna hijau.

Jika Apache gagal start karena port 80 dipakai program lain, ubah port Apache (Config > httpd.conf)
atau tutup program yang memakainya. Jika Anda memakai port lain, misalnya 8080, alamat website menjadi
`http://localhost:8080/sipelfa-fmipa/`.

## 3. Memeriksa struktur tabel (satu kali, sekitar 2 menit)

Kode ditulis berdasarkan struktur yang Anda jelaskan. Sebelum mencoba, pastikan kolom-kolomnya cocok.
Perintah di bawah hanya MELIHAT struktur dan tidak mengubah apa pun.

1. Buka `http://localhost/phpmyadmin`.
2. Klik database `db_sipelfa_fmipa`, lalu klik tab **SQL**.
3. Jalankan satu per satu:

```sql
SHOW COLUMNS FROM users;
SHOW COLUMNS FROM laporan;
SHOW COLUMNS FROM riwayat_status;
```

Yang perlu dicocokkan:

| Tabel | Kolom | Yang diharapkan |
|---|---|---|
| users | nim | VARCHAR(30), UNIQUE (sudah Anda atur) |
| users | password_hash | VARCHAR **minimal 60**, sebaiknya 255 |
| users | role | berisi nilai `mahasiswa` dan `admin` |
| users | status_akun | berisi nilai `aktif` dan `nonaktif` |
| laporan | foto | VARCHAR minimal 100 (yang disimpan: `uploads/laporan/namafile.jpg`) |
| laporan | status | menerima `Diajukan`, `Diverifikasi`, `Diproses`, `Selesai` |
| laporan | kategori_masalah, lokasi | panjang minimal 100 karakter |
| riwayat_status | keterangan | panjang minimal 255 karakter, atau TEXT |

**Jika ada yang tidak cocok:**

- Kolom teks lebih pendek dari angka di atas: jangan ubah database dulu. Cukup buka
  `includes/functions.php` dan kecilkan angka `BATAS_KATEGORI`, `BATAS_LOKASI`, `BATAS_KETERANGAN`
  agar sama dengan panjang kolom Anda.
- `password_hash` lebih pendek dari 60: hash password akan terpotong dan login selalu gagal.
  Dalam kasus ini satu-satunya perbaikan adalah memperpanjang kolom. Perintahnya:

  ```sql
  ALTER TABLE users MODIFY password_hash VARCHAR(255) NOT NULL;
  ```

  Jalankan hanya jika memang diperlukan.

Konfigurasi koneksi database ada di satu file: `config/database.php`
(host `localhost`, user `root`, password kosong, database `db_sipelfa_fmipa`).

## 4. Membuat akun admin pertama (tanpa phpMyAdmin)

1. Pastikan Apache dan MySQL sudah Start.
2. Buka browser, ketik alamat: `http://localhost/sipelfa-fmipa/setup_admin.php`
3. Isi formulir:
   - **Nama lengkap admin**: nama Anda.
   - **NIM admin**: tentukan sendiri, huruf dan angka saja. Contoh: `ADMIN001`.
   - **Password**: tentukan sendiri, minimal 8 karakter. Catat di tempat aman.
   - **Konfirmasi password**: ketik password yang sama.
4. Klik **Buat akun admin**.
5. Jika berhasil, Anda otomatis dibawa ke halaman login dengan pesan sukses.

Halaman setup hanya bisa dipakai jika belum ada admin. Setelah akun admin ada, halaman ini terkunci
dan menolak pembuatan akun baru. Tidak ada pendaftaran admin lewat jalur publik.

## 5. Login sebagai admin

1. Buka `http://localhost/sipelfa-fmipa/login.php`
2. Isi **NIM** dan **Password** admin yang tadi Anda buat.
3. Klik **Masuk**. Anda akan masuk ke Dashboard Admin.

## 6. Menghapus setup_admin.php (wajib, demi keamanan)

Setelah berhasil login sebagai admin:

1. Buka File Explorer, masuk ke `C:\xampp\htdocs\sipelfa-fmipa\`.
2. Klik kanan file `setup_admin.php`, pilih **Delete**.

Atau lewat Visual Studio Code: klik kanan `setup_admin.php` di panel Explorer, pilih **Delete**.
Selama file itu masih ada, halaman login dan dashboard admin menampilkan peringatan.

## 7. Mencoba sebagai mahasiswa

1. Keluar dari akun admin (tombol **Keluar** di sidebar).
2. Di halaman login klik **Daftar di sini**, isi nama, NIM, password, dan konfirmasi.
3. Login dengan NIM tersebut, lalu buka **Buat laporan**.
4. Isi kategori (ketik bebas), lokasi, deskripsi, dan pilih foto. Pratinjau foto muncul sebelum dikirim.
5. Kembali login sebagai admin untuk melihat laporan, memperbesar foto, dan mengubah status.

## 8. Struktur folder

```
sipelfa-fmipa/
  index.php  login.php  register.php  logout.php  setup_admin.php  foto.php
  config/database.php            pengaturan koneksi database (satu-satunya)
  includes/
    functions.php                fungsi umum, validasi, CSRF, format tanggal
    auth.php                     login, session, pemeriksaan role
    upload.php                   validasi dan penyimpanan foto
    components.php               ikon, notifikasi, stepper, timeline, pagination
    header.php sidebar.php footer.php
  mahasiswa/  dashboard, buat_laporan, laporan_saya, detail_laporan
  admin/      dashboard, data_laporan, detail_laporan, data_mahasiswa, riwayat_laporan
  assets/css/style.css  assets/js/app.js
  uploads/laporan/               foto kerusakan (dilindungi .htaccess)
```

Foto tidak dibuka langsung lewat alamat file. Foto ditampilkan lewat `foto.php`, yang memeriksa
bahwa pemintanya adalah admin atau pemilik laporan.

## 9. Pemecahan masalah

| Gejala | Penyebab dan solusi |
|---|---|
| "Database belum terhubung" | MySQL belum Start di XAMPP, atau nama database di `config/database.php` tidak cocok. |
| Halaman "Terjadi kesalahan pada sistem" | Buka `includes/functions.php`, ubah `APP_DEBUG` menjadi `true`, muat ulang untuk melihat detail. Kembalikan ke `false` setelah selesai. Log juga ada di `C:\xampp\apache\logs\error.log`. |
| Login selalu salah padahal password benar | Kolom `password_hash` terlalu pendek (lihat bagian 3). Akun yang terlanjur dibuat perlu dibuat ulang setelah kolom diperpanjang. |
| Foto tidak muncul | Pastikan folder `uploads/laporan/` ada. Pastikan `foto.php` bisa dibuka setelah login. |
| Foto gagal diunggah | Ukuran maksimal 5 MB, format JPG/PNG/WEBP. Pastikan folder `uploads/laporan/` bisa ditulis. |
| Tampilan tanpa gaya (polos) | Folder proyek tidak berada di `htdocs`, atau alamat dibuka dengan cara yang salah. Buka lewat `http://localhost/...`, bukan dengan klik dua kali file. |
| Terkunci 60 detik saat login | Perlindungan dari 5 kali salah password berturut-turut. Tunggu sebentar. |

Catatan: fitur lupa password tidak termasuk dalam spesifikasi. Admin tidak dapat melihat password
mahasiswa, dan tidak ada tombol reset password.

## 10. Daftar uji manual

Kode ini ditulis dan diperiksa secara statis (kelengkapan file, keseimbangan kode, path include),
tetapi **belum dijalankan** karena lingkungan penulisnya tidak memiliki PHP dan MySQL.
Centang daftar ini sambil mencoba di XAMPP Anda. Jika ada yang gagal, catat pesan yang muncul.

- [ ] 1. Halaman login terbuka tanpa pesan "Database belum terhubung"
- [ ] 2. Registrasi mahasiswa berhasil
- [ ] 3. Registrasi dengan NIM yang sama ditolak
- [ ] 4. Di phpMyAdmin, kolom `password_hash` berisi teks acak diawali `$2y$` (bukan password asli)
- [ ] 5. Login mahasiswa dengan NIM dan password berhasil, masuk ke dashboard mahasiswa
- [ ] 6. Login admin berhasil, masuk ke dashboard admin
- [ ] 7. Mahasiswa yang membuka `.../admin/dashboard.php` dialihkan kembali
- [ ] 8. Mahasiswa dapat mengirim laporan
- [ ] 9. Kolom kategori masalah berupa kotak ketik biasa (bukan dropdown)
- [ ] 10. Foto berhasil diunggah, pratinjau muncul sebelum dikirim
- [ ] 11. Foto muncul di detail laporan mahasiswa
- [ ] 12. Foto muncul di detail laporan admin dan bisa diperbesar
- [ ] 13. Admin melihat laporan semua mahasiswa
- [ ] 14. Admin mengubah status (muncul konfirmasi), status berubah
- [ ] 15. Riwayat status bertambah dan riwayat lama tidak hilang
- [ ] 16. Mahasiswa B tidak bisa membuka laporan mahasiswa A (ubah angka `id` di alamat)
- [ ] 17. Admin dapat menonaktifkan dan mengaktifkan akun mahasiswa; akun nonaktif ditolak saat login
- [ ] 18. Tombol Keluar berfungsi
- [ ] 19. Membuka `.../mahasiswa/dashboard.php` tanpa login dialihkan ke halaman login
- [ ] 20. Setelah admin dibuat, `setup_admin.php` menampilkan "Setup admin sudah terkunci"
- [ ] 21. Tampilan rapi di laptop dan di smartphone (buka lewat jaringan yang sama atau mode responsif browser)

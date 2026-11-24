-- =====================================================================
-- MIGRASI: fitur foto bukti perbaikan (SIPELFA FMIPA UNCEN)
-- Database : db_sipelfa_fmipa
-- Aman untuk database yang sudah berisi data: hanya MENAMBAH dua kolom
-- ke tabel laporan. Tidak ada tabel atau kolom yang dihapus, diubah
-- namanya, atau dikosongkan. Data lama tetap utuh (kolom baru bernilai NULL).
-- Jalankan SEKALI saja lewat phpMyAdmin > db_sipelfa_fmipa > tab SQL.
-- =====================================================================

ALTER TABLE laporan
  -- Lokasi file foto bukti perbaikan, misalnya: uploads/perbaikan/ab12cd....jpg
  -- NULL = belum ada foto perbaikan (semua laporan lama otomatis begini).
  ADD COLUMN foto_perbaikan VARCHAR(255) NULL DEFAULT NULL AFTER foto,
  -- Tanggal fasilitas diperbaiki (diisi admin saat mengunggah foto).
  -- NULL = belum ada tanggal perbaikan.
  ADD COLUMN tanggal_perbaikan DATE NULL DEFAULT NULL AFTER foto_perbaikan;

-- Untuk memeriksa hasilnya (hanya membaca, tidak mengubah apa pun):
-- SHOW COLUMNS FROM laporan;

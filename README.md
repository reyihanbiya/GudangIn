# GudangIn

GudangIn adalah aplikasi web POS dan persediaan untuk toko bangunan, dibuat dengan Laravel 12, Livewire, dan MariaDB.

## Menjalankan di Windows/XAMPP

1. Pastikan PHP 8.2+, Composer, Node.js/npm, dan MariaDB aktif.
2. Buat database bernama `gudangin`, lalu impor skema awal dari `gudangin.sql` melalui phpMyAdmin jika tabelnya belum ada.
3. Periksa konfigurasi koneksi `DB_*` di `.env`.
4. Pasang dependensi jika belum tersedia:

   ```powershell
   composer install
   npm install
   ```

5. Jalankan migrasi untuk membuat tabel pendukung Laravel dan sequence ID:

   ```powershell
   php artisan migrate
   ```

6. Jalankan aplikasi dan aset frontend:

   ```powershell
   composer run dev
   ```

   Script ini menjalankan Laravel, worker antrean, dan Vite. Pail tidak dijalankan karena membutuhkan ekstensi `pcntl` yang tidak tersedia pada Windows.
7. Buka [http://127.0.0.1:8000](http://127.0.0.1:8000). Pada penggunaan pertama, halaman akan meminta pembuatan akun Owner dan Pegawai.

Biarkan terminal tetap terbuka selama aplikasi digunakan. Halaman penyiapan hanya tersedia sebelum akun pertama dibuat.

## Fitur yang tersedia

- Login terpisah untuk Pegawai dan Owner, termasuk pembuatan akun awal satu kali.
- Dashboard ringkasan untuk kedua peran.
- Pengelolaan pelanggan, supplier, dan persediaan.
- Pencarian dan pagination pada daftar data.
- Kartu stok untuk melihat transaksi barang masuk dan keluar.
- Transaksi penjualan dengan validasi stok dan pengurangan stok otomatis.
- Transaksi pembelian dengan stok bertambah setelah barang diterima.
- Laporan penjualan dan pembelian dengan filter rentang tanggal untuk Owner.
- OCR nota pembelian lokal, hasilnya dapat diperiksa sebelum transaksi disimpan.

### Menyiapkan OCR nota

Fitur OCR memerlukan Tesseract OCR yang terpasang secara lokal dengan data bahasa yang sesuai. Pastikan `tesseract.exe` dapat dijalankan dari terminal, atau atur `TESSERACT_BINARY` di `.env`. Bahasa dan batas waktu dapat diatur melalui `TESSERACT_LANGUAGES` dan `TESSERACT_TIMEOUT`. OCR berjalan di komputer/server ini; gambar nota tidak dikirim ke layanan pihak ketiga.

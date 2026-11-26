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

5. Jalankan migrasi untuk membuat tabel pendukung Laravel, sequence ID, dan pembayaran Xendit:

   ```powershell
   php artisan migrate
   ```

   Barang lama mendapat satuan sementara `unit`; sesuaikan satuannya melalui menu **Persediaan** sebelum melanjutkan input transaksi.

6. Checkout berjalan dalam **mode simulasi** secara default (`XENDIT_SIMULATION=true`): QR hanya contoh, tidak ada permintaan atau pembayaran sungguhan ke Xendit, dan transaksi baru dicatat setelah tombol **Bayar (Simulasi)** ditekan. Untuk mengaktifkan integrasi Xendit sungguhan secara eksplisit, ubah flag menjadi `XENDIT_SIMULATION=false`, isi `XENDIT_SECRET_KEY` dan `XENDIT_WEBHOOK_TOKEN`, lalu daftarkan URL webhook `https://alamat-aplikasi/api/xendit/webhook` pada dashboard Xendit untuk event `payment.capture`, `payment.failure`, dan `payment_request.expiry`. Gunakan secret key test saat pengembangan.

7. Jalankan aplikasi dan aset frontend:

   ```powershell
   composer run dev
   ```

   Script ini menjalankan Laravel, worker antrean, dan Vite. Pail tidak dijalankan karena membutuhkan ekstensi `pcntl` yang tidak tersedia pada Windows.
8. Buka [http://127.0.0.1:8000](http://127.0.0.1:8000). Pada penggunaan pertama, halaman akan meminta pembuatan satu atau lebih akun Owner dan Pegawai.

Biarkan terminal tetap terbuka selama aplikasi digunakan. Halaman penyiapan hanya tersedia sebelum akun pertama dibuat.

## Fitur yang tersedia

- Pendaftaran Pegawai tersedia dari halaman masuk kapan saja; akun Owner hanya dapat dibuat oleh Owner yang sudah login. Penyiapan awal juga dapat membuat beberapa akun untuk kedua peran sekaligus.
- Dashboard ringkasan untuk kedua peran.
- Dashboard menampilkan penjualan/pembelian harian, tren penjualan 7 hari, serta perkiraan stok habis dalam 7 hari berdasarkan laju penjualan pada satuan yang sama.
- Pengelolaan pelanggan, supplier, dan persediaan.
- ID pengiriman unik untuk setiap transaksi penjualan; daftar Pengiriman menyediakan surat jalan per transaksi.
- Satuan stok per barang (mis. sak, kg, meter, atau liter) dengan jumlah hingga tiga angka desimal.
- Pencarian dan pagination pada daftar data.
- Kartu stok untuk melihat transaksi barang masuk dan keluar.
- Transaksi penjualan dengan validasi stok dan pengurangan stok otomatis.
- Checkout QRIS dalam mode simulasi lokal secara default, atau melalui Xendit jika integrasi live diaktifkan secara eksplisit.
- Transaksi pembelian dengan stok bertambah setelah barang diterima.
- Laporan penjualan dan pembelian dengan filter rentang tanggal untuk Owner.
- OCR nota pembelian lokal, hasilnya dapat diperiksa sebelum transaksi disimpan.

### Menyiapkan OCR nota

Fitur OCR memerlukan Tesseract OCR yang terpasang secara lokal dengan data bahasa yang sesuai. Pastikan `tesseract.exe` dapat dijalankan dari terminal, atau atur `TESSERACT_BINARY` di `.env`. Bahasa dan batas waktu dapat diatur melalui `TESSERACT_LANGUAGES` dan `TESSERACT_TIMEOUT`. OCR berjalan di komputer/server ini; gambar nota tidak dikirim ke layanan pihak ketiga.

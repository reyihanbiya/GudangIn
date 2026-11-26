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

5. Jalankan migrasi untuk membuat tabel pendukung Laravel, sequence ID, pembayaran, dan pelacakan:

   ```powershell
   php artisan migrate
   ```

   Barang lama mendapat satuan sementara `unit`; sesuaikan satuannya melalui menu **Persediaan** sebelum melanjutkan input transaksi.

6. Checkout QRIS untuk kasir tetap menggunakan Xendit dan **mode simulasi** secara default (`XENDIT_SIMULATION=true`). Untuk Xendit live, ubah flag menjadi `false`, isi `XENDIT_SECRET_KEY` dan `XENDIT_WEBHOOK_TOKEN`, lalu daftarkan URL `https://alamat-aplikasi/api/xendit/webhook` untuk event `payment.capture`, `payment.failure`, dan `payment_request.expiry`.
7. Pesanan customer mendukung transfer manual, pembayaran tunai di toko, COD, serta QRIS/transfer otomatis melalui Midtrans. Buat akun Midtrans, salin Server Key dan Client Key dari dashboard (gunakan kredensial Sandbox saat pengembangan), lalu isi `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, dan `MIDTRANS_IS_PRODUCTION=false`. Daftarkan `https://alamat-aplikasi/api/midtrans/notification` sebagai Payment Notification URL. Tanpa Server Key, pilihan QRIS/transfer otomatis tidak ditampilkan; transfer manual tetap tersedia. Atur `STORE_BANK_NAME`, `STORE_BANK_ACCOUNT`, `STORE_BANK_HOLDER`, dan `STORE_DELIVERY_FEE` sesuai kebijakan toko.
8. Untuk peta, aktifkan Google Maps JavaScript API dan Places API di Google Cloud, buat API key, batasi key berdasarkan HTTP referrer/domain, lalu isi `GOOGLE_MAPS_API_KEY`, `STORE_LATITUDE`, dan `STORE_LONGITUDE`. Koordinat toko dipakai sebagai titik awal peta/rute. Tanpa key, alamat pengiriman tetap dapat dimasukkan manual; pemilihan pin dan pelacakan peta memerlukan key tersebut.
9. Owner dapat mengubah jabatan Pegawai menjadi **Kurir** melalui menu **Akun Owner**. Kurir memakai akun Pegawai yang sama; browser perlu izin lokasi dan aplikasi harus diakses melalui HTTPS (localhost diizinkan browser untuk pengembangan).

10. Jalankan aplikasi dan aset frontend:

   ```powershell
   composer run dev
   ```

   Script ini menjalankan Laravel, worker antrean, dan Vite. Pail tidak dijalankan karena membutuhkan ekstensi `pcntl` yang tidak tersedia pada Windows.
11. Buka [http://127.0.0.1:8000](http://127.0.0.1:8000). Pada penggunaan pertama, halaman akan meminta pembuatan satu atau lebih akun Owner dan Pegawai.

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
- Katalog dan checkout customer yang terhubung ke persediaan, transfer manual dengan bukti privat dan verifikasi Owner/Pegawai, tunai di toko, COD, serta pembayaran Midtrans QRIS/VA dengan webhook bertanda tangan.
- Pelacakan pesanan tanpa login menggunakan kode pesanan dan nomor WhatsApp, riwayat status/pembayaran, pilihan lokasi melalui Google Maps, serta pembaruan GPS kurir saat pesanan dalam perjalanan.
- Transaksi pembelian dengan stok bertambah setelah barang diterima.
- Laporan penjualan dan pembelian dengan filter rentang tanggal untuk Owner.
- OCR nota pembelian lokal, hasilnya dapat diperiksa sebelum transaksi disimpan.

### Menyiapkan OCR nota

Fitur OCR memerlukan Tesseract OCR yang terpasang secara lokal dengan data bahasa yang sesuai. Pastikan `tesseract.exe` dapat dijalankan dari terminal, atau atur `TESSERACT_BINARY` di `.env`. Bahasa dan batas waktu dapat diatur melalui `TESSERACT_LANGUAGES` dan `TESSERACT_TIMEOUT`. OCR berjalan di komputer/server ini; gambar nota tidak dikirim ke layanan pihak ketiga.

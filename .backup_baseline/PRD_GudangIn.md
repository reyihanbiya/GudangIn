# Product Requirements Document (PRD)

## GudangIn --- Sistem Point of Sale dan Persediaan Toko Bangunan

**Tagline:** Kelola Stok, Tumbuh Bersama\
**Jenis sistem:** Aplikasi berbasis web\
**Versi dokumen:** 1.0 (Draft untuk ditinjau)

------------------------------------------------------------------------

## 1. Overview & Purpose

### 1.1 Overview

GudangIn merupakan aplikasi Point of Sale (POS) berbasis web yang
dirancang untuk membantu toko bangunan dalam mengelola transaksi
penjualan, pembelian, dan persediaan barang. Sistem ini dikembangkan
untuk mengurangi proses pencatatan manual, mempermudah pengecekan stok,
serta membantu pemilik toko memperoleh informasi transaksi melalui
laporan.

GudangIn menyediakan akses bagi Pegawai untuk mengelola kegiatan
operasional, sedangkan Owner dapat melihat laporan penjualan dan
pembelian. Sistem juga menyediakan fitur OCR untuk membaca informasi
dari nota pembelian sehingga hasil pembacaan dapat diperiksa dan
divalidasi sebelum disimpan.

### 1.2 Purpose

Tujuan pengembangan GudangIn adalah: 1. Mempermudah pencatatan transaksi
penjualan dan pembelian. 2. Membantu pegawai memantau persediaan barang.
3. Mengurangi kesalahan pencatatan dan perhitungan yang dilakukan secara
manual. 4. Mempermudah pencatatan detail pembelian melalui bantuan OCR
pada nota. 5. Membantu Owner memantau aktivitas toko melalui laporan
penjualan dan pembelian.

### 1.3 Product Scope

Cakupan utama GudangIn meliputi autentikasi pengguna, pengelolaan data
pelanggan, persediaan, transaksi penjualan, transaksi pembelian,
pemrosesan nota pembelian menggunakan OCR, serta penyajian laporan
penjualan dan pembelian.

------------------------------------------------------------------------

## 2. User Roles

GudangIn memiliki dua jenis pengguna yang dapat mengakses sistem, yaitu
Pegawai dan Owner.

  -----------------------------------------------------------------------
  Pengguna                Peran                   Hak Akses
  ----------------------- ----------------------- -----------------------
  Pegawai                 Pengguna yang           Login, mengelola data
                          menjalankan kegiatan    pelanggan, mengelola
                          operasional toko. Dalam persediaan, mencatat
                          proses bisnis,          transaksi penjualan dan
                          aktivitas operasional   pembelian, serta
                          melibatkan bagian kasir memproses dan
                          dan gudang.             memvalidasi hasil OCR
                                                  nota pembelian.

  Owner                   Pemilik toko yang       Login melalui mekanisme
                          memantau aktivitas dan  terpisah dan melihat
                          hasil operasional.      laporan penjualan serta
                                                  laporan pembelian.
  -----------------------------------------------------------------------

**Catatan:** Supplier merupakan pihak eksternal yang memasok barang dan
menyerahkan nota pembelian. Supplier tidak melakukan login atau
menggunakan sistem GudangIn. Customer juga merupakan pihak eksternal
yang melakukan transaksi dengan toko.

------------------------------------------------------------------------

## 3. Core Features & User Stories

### 3.1 Login dan Authentication

Sistem menyediakan mekanisme login yang berbeda untuk Pegawai dan Owner.
Akun Pegawai disimpan pada tabel `pegawai`, sedangkan akun Owner
menggunakan tabel `owner` yang ditambahkan secara terpisah.

**User stories:** - Sebagai Pegawai, saya ingin login agar dapat
mengakses fitur operasional sesuai peran saya. - Sebagai Owner, saya
ingin login melalui akses khusus agar dapat melihat laporan toko. -
Sebagai pengguna, saya ingin sistem memvalidasi akun agar akses hanya
diberikan kepada pengguna yang terdaftar.

### 3.2 Pengelolaan Data Pelanggan

Pegawai dapat mengelola informasi pelanggan yang diperlukan dalam
pencatatan transaksi penjualan.

**User stories:** - Sebagai Pegawai, saya ingin menambahkan data
pelanggan agar informasi pelanggan dapat digunakan saat transaksi. -
Sebagai Pegawai, saya ingin melihat dan memperbarui data pelanggan agar
informasi tetap sesuai. - Sebagai Pegawai, saya ingin menghapus data
pelanggan yang tidak lagi diperlukan sesuai ketentuan sistem.

### 3.3 Pengelolaan Persediaan

Pegawai dapat mengelola data barang dan memantau jumlah stok. Sistem
menyediakan informasi kartu stok untuk melihat pergerakan barang masuk
dan keluar.

**User stories:** - Sebagai Pegawai, saya ingin melihat data barang dan
jumlah stok agar dapat mengetahui ketersediaan barang. - Sebagai
Pegawai, saya ingin mengelola data persediaan agar informasi barang
tetap terbarui. - Sebagai Pegawai, saya ingin melihat kartu stok agar
dapat menelusuri barang masuk dan barang keluar.

### 3.4 Transaksi Penjualan

Pegawai mencatat transaksi penjualan dengan memilih pelanggan, barang,
dan jumlah barang. Sistem memeriksa ketersediaan stok serta menghitung
subtotal dan total transaksi.

**User stories:** - Sebagai Pegawai, saya ingin mencatat transaksi
penjualan agar transaksi tersimpan dalam sistem. - Sebagai Pegawai, saya
ingin sistem memeriksa stok agar penjualan tidak melebihi persediaan
yang tersedia. - Sebagai Pegawai, saya ingin sistem menghitung subtotal
dan total agar perhitungan transaksi lebih mudah. - Sebagai Pegawai,
saya ingin stok diperbarui berdasarkan transaksi penjualan yang berhasil
disimpan.

### 3.5 Transaksi Pembelian

Pegawai mencatat pembelian barang dari supplier, termasuk informasi
supplier, barang, harga beli, jumlah, dan status pengiriman.

**User stories:** - Sebagai Pegawai, saya ingin mencatat pembelian dari
supplier agar data pembelian terdokumentasi. - Sebagai Pegawai, saya
ingin memasukkan rincian barang yang dibeli agar harga dan jumlah
pembelian tersimpan. - Sebagai Pegawai, saya ingin mengetahui status
pengiriman agar proses pembelian dapat dipantau.

### 3.6 OCR Nota Pembelian

Sistem menyediakan fitur OCR untuk membaca informasi pada gambar nota
pembelian. Hasil pembacaan ditampilkan agar dapat diperiksa dan
diperbaiki oleh Pegawai. Setelah divalidasi, data hasil ekstraksi
disimpan ke tabel `detail_pembelian`.

**User stories:** - Sebagai Pegawai, saya ingin mengunggah gambar nota
pembelian agar informasi pada nota dapat dibaca oleh sistem. - Sebagai
Pegawai, saya ingin sistem membaca informasi nota menggunakan OCR agar
input detail pembelian lebih efisien. - Sebagai Pegawai, saya ingin
memeriksa dan memperbaiki hasil pembacaan agar data yang disimpan sesuai
dengan nota. - Sebagai Pegawai, saya ingin menyimpan hasil OCR yang
telah divalidasi ke detail pembelian.

**Catatan:** Struktur database yang tersedia menyimpan data barang,
harga beli, jumlah, dan subtotal pada `detail_pembelian`. Penyimpanan
file gambar nota atau teks OCR mentah belum ditunjukkan pada struktur
SQL yang diperiksa.

### 3.7 Laporan Penjualan dan Pembelian

Owner dapat melihat laporan penjualan dan pembelian yang disusun
berdasarkan data transaksi dalam sistem.

**User stories:** - Sebagai Owner, saya ingin melihat laporan penjualan
agar dapat memantau hasil transaksi penjualan. - Sebagai Owner, saya
ingin melihat laporan pembelian agar dapat memantau aktivitas pembelian
barang.

------------------------------------------------------------------------

## 4. User Flow

### 4.1 Login

1.  Pengguna membuka halaman login GudangIn.
2.  Pengguna mengakses mekanisme login sesuai jenis akun, yaitu Pegawai
    atau Owner.
3.  Pengguna memasukkan username dan password.
4.  Sistem memvalidasi data akun.
5.  Jika valid, sistem mengarahkan pengguna ke halaman sesuai hak akses.
6.  Jika tidak valid, sistem menampilkan pemberitahuan bahwa login
    gagal.

### 4.2 Pengelolaan Data Pelanggan

1.  Pegawai membuka menu pelanggan.
2.  Sistem menampilkan daftar pelanggan.
3.  Pegawai dapat menambahkan, melihat, mengubah, atau menghapus data
    pelanggan.
4.  Sistem menyimpan perubahan data.

### 4.3 Pengelolaan Persediaan

1.  Pegawai membuka menu persediaan.
2.  Sistem menampilkan data barang dan jumlah stok.
3.  Pegawai mengelola informasi barang sesuai kebutuhan.
4.  Pegawai dapat membuka kartu stok untuk melihat riwayat barang masuk
    dan keluar.

### 4.4 Transaksi Penjualan

1.  Pegawai membuka menu penjualan.
2.  Pegawai memilih pelanggan.
3.  Pegawai memilih barang dan memasukkan jumlah barang.
4.  Sistem memeriksa ketersediaan stok.
5.  Jika stok mencukupi, sistem menghitung subtotal dan total transaksi.
6.  Pegawai menyelesaikan pencatatan transaksi.
7.  Sistem menyimpan transaksi dan memperbarui stok.
8.  Jika stok tidak mencukupi, pegawai dapat melanjutkan proses
    pengadaan melalui pembelian kepada supplier.

### 4.5 Transaksi Pembelian

1.  Pegawai membuka menu pembelian.
2.  Pegawai memilih supplier.
3.  Pegawai memasukkan informasi barang yang dibeli atau memproses nota
    pembelian menggunakan OCR.
4.  Sistem menyimpan data pembelian dan detail pembelian setelah data
    diperiksa.
5.  Status pengiriman pembelian dapat dipantau melalui sistem.
6.  Setelah barang diterima dan data pembelian dikonfirmasi, persediaan
    diperbarui sesuai proses yang diterapkan.

### 4.6 OCR Nota Pembelian

1.  Pegawai membuka fitur OCR nota pembelian.
2.  Pegawai mengunggah gambar nota.
3.  Sistem membaca informasi pada nota menggunakan OCR.
4.  Sistem menampilkan hasil pembacaan.
5.  Pegawai memeriksa dan memperbaiki hasil pembacaan apabila
    diperlukan.
6.  Pegawai memvalidasi hasil OCR.
7.  Sistem menyimpan data hasil ekstraksi yang telah divalidasi ke
    `detail_pembelian`.

### 4.7 Melihat Laporan

1.  Owner login melalui mekanisme khusus Owner.
2.  Owner membuka menu laporan.
3.  Owner memilih laporan penjualan atau laporan pembelian.
4.  Sistem menampilkan laporan berdasarkan data transaksi yang
    tersimpan.

------------------------------------------------------------------------

## 5. UI/UX Requirements

### 5.1 Konsep Desain

Antarmuka GudangIn dirancang dengan tampilan sederhana, rapi, dan mudah
digunakan oleh Pegawai maupun Owner. Desain menggunakan konsep Warm Sand
dengan kombinasi warna cokelat dan krem untuk memberikan kesan hangat,
profesional, dan nyaman dilihat.

### 5.2 Palet Warna

  Nama Warna     Kode Warna   Penggunaan
  -------------- ------------ -----------------------------------
  Dark Brown     `#764C2D`    Warna utama dan tombol utama
  Medium Brown   `#A67551`    Aksen dan elemen pendukung
  Cream          `#EFE1D6`    Latar belakang elemen
  Light Cream    `#F6F0EB`    Latar belakang halaman
  Text           `#302820`    Teks utama
  Secondary      `#66574B`    Teks pendukung
  White          `#FFFFFF`    Latar belakang kartu dan formulir

### 5.3 Tipografi

GudangIn menggunakan jenis huruf **Inter** karena memiliki tampilan
sederhana, modern, dan mudah dibaca. Ukuran serta ketebalan huruf
disesuaikan dengan hierarki informasi agar pengguna dapat membedakan
judul, isi, dan keterangan.

### 5.4 Komponen Antarmuka

Komponen antarmuka yang digunakan dalam sistem meliputi: - **Navigasi:**
Memudahkan pengguna berpindah antarhalaman sesuai hak akses. -
**Formulir:** Digunakan untuk memasukkan dan mengubah data pelanggan,
persediaan, penjualan, dan pembelian. - **Tabel:** Menampilkan data
transaksi, persediaan, pelanggan, dan supplier. - **Tombol aksi:**
Digunakan untuk menjalankan fungsi tambah, ubah, hapus, simpan, dan
validasi. - **Notifikasi:** Memberikan informasi mengenai keberhasilan
atau kegagalan suatu proses. - **Laporan:** Menampilkan informasi
penjualan dan pembelian yang dapat diakses oleh Owner.

### 5.5 Prinsip Kemudahan Penggunaan

Antarmuka sistem harus memiliki navigasi yang jelas, konsistensi
tampilan pada setiap halaman, serta informasi yang mudah dipahami.
Formulir dibuat secara terstruktur untuk mengurangi kesalahan input.
Tampilan sistem juga perlu menyesuaikan ukuran layar perangkat yang
digunakan.

------------------------------------------------------------------------

## 6. Constraints & Out of Scope

### 6.1 Constraints (Batasan Sistem)

1.  Sistem dikembangkan dalam bentuk aplikasi berbasis web.
2.  Sistem memiliki dua jenis pengguna, yaitu Pegawai dan Owner, dengan
    mekanisme login yang berbeda.
3.  Pegawai memiliki akses untuk mengelola data pelanggan, persediaan,
    transaksi penjualan, dan pembelian.
4.  Owner memiliki akses untuk melihat laporan penjualan dan pembelian.
5.  Proses pembacaan nota pembelian menggunakan teknologi OCR.
6.  Hasil OCR harus diperiksa dan divalidasi oleh Pegawai sebelum
    disimpan ke database.
7.  Data hasil OCR yang telah divalidasi disimpan pada tabel
    `detail_pembelian`.
8.  Sistem diakses melalui browser pada perangkat yang terhubung ke
    jaringan yang dibutuhkan aplikasi.

### 6.2 Out of Scope (Di Luar Cakupan Sistem)

Fitur dan proses berikut tidak termasuk dalam cakupan pengembangan
GudangIn: 1. Aplikasi mobile khusus untuk Android maupun iOS. 2. Akses
sistem secara langsung oleh pelanggan. 3. Akses sistem secara langsung
oleh supplier. 4. Integrasi otomatis dengan sistem eksternal milik
supplier. 5. Proses pembayaran melalui payment gateway. 6. Integrasi
pengiriman barang dengan jasa ekspedisi.

------------------------------------------------------------------------

## 7. Database Design

### 7.1 Struktur Tabel Database

Database GudangIn menggunakan MariaDB untuk menyimpan data pengguna,
pelanggan, persediaan, transaksi penjualan, transaksi pembelian, dan
pencatatan akuntansi.

  -----------------------------------------------------------------------
  No.                     Nama Tabel              Fungsi
  ----------------------- ----------------------- -----------------------
  1                       `pegawai`               Menyimpan data akun
                                                  Pegawai untuk mengakses
                                                  sistem.

  2                       `owner`                 Menyimpan data akun
                                                  Owner dengan mekanisme
                                                  login terpisah. Tabel
                                                  ini merupakan tambahan
                                                  rancangan.

  3                       `pelanggan`             Menyimpan informasi
                                                  pelanggan.

  4                       `supplier`              Menyimpan informasi
                                                  pemasok barang.

  5                       `persediaan`            Menyimpan data barang,
                                                  kategori, dan jumlah
                                                  stok.

  6                       `penjualan`             Menyimpan data utama
                                                  transaksi penjualan.

  7                       `detail_penjualan`      Menyimpan rincian
                                                  barang dalam transaksi
                                                  penjualan.

  8                       `pembelian`             Menyimpan data utama
                                                  transaksi pembelian
                                                  dari supplier.

  9                       `detail_pembelian`      Menyimpan rincian
                                                  barang yang dibeli,
                                                  harga beli, jumlah, dan
                                                  subtotal.

  10                      `coa`                   Menyimpan daftar kode
                                                  akun, nama akun, dan
                                                  jenis akun.

  11                      `jurnal_umum`           Menyimpan pencatatan
                                                  akuntansi berdasarkan
                                                  akun, debit, dan
                                                  kredit.
  -----------------------------------------------------------------------

### 7.2 Relasi Antar Tabel

Relasi antar tabel dalam database GudangIn meliputi: 1. Tabel `pegawai`
berelasi dengan tabel `penjualan`, `pembelian`, dan `persediaan`. 2.
Tabel `pelanggan` berelasi dengan tabel `penjualan`. 3. Tabel `supplier`
berelasi dengan tabel `pembelian`. 4. Tabel `penjualan` berelasi dengan
tabel `detail_penjualan`. 5. Tabel `pembelian` berelasi dengan tabel
`detail_pembelian`. 6. Tabel `persediaan` berelasi dengan tabel
`detail_penjualan` dan `detail_pembelian`. 7. Tabel `coa` berelasi
dengan tabel `jurnal_umum`. 8. Tabel `penjualan` dan `pembelian`
masing-masing dapat dihubungkan dengan tabel `jurnal_umum`.

Tabel `owner` digunakan untuk autentikasi akun Owner dan tidak memiliki
relasi langsung dengan tabel transaksi.

### 7.3 Database View

Database GudangIn memiliki tiga view untuk membantu penyajian informasi:

  -------------------------------------------------------------------------
  No.                     Nama View                 Fungsi
  ----------------------- ------------------------- -----------------------
  1                       `view_kartu_stok`         Menampilkan riwayat
                                                    barang masuk dan barang
                                                    keluar berdasarkan
                                                    transaksi pembelian dan
                                                    penjualan.

  2                       `view_laporanpembelian`   Menampilkan informasi
                                                    pembelian, supplier,
                                                    pegawai, status
                                                    pengiriman, dan total
                                                    pembelian.

  3                       `view_laporanpenjualan`   Menampilkan informasi
                                                    penjualan, pelanggan,
                                                    pegawai, dan total
                                                    penjualan.
  -------------------------------------------------------------------------

### 7.4 Penyimpanan Hasil OCR

Fitur OCR digunakan untuk membaca informasi dari nota pembelian. Hasil
pembacaan yang telah diperiksa dan divalidasi oleh Pegawai disimpan ke
tabel `detail_pembelian`. Data yang disimpan meliputi barang, harga
beli, jumlah barang, dan subtotal yang dihitung berdasarkan harga beli
dikalikan jumlah barang.

**Catatan implementasi:** Pada struktur SQL awal, tabel `owner` belum
tersedia dan perlu ditambahkan. Struktur SQL yang diperiksa juga belum
menunjukkan kolom untuk menyimpan gambar nota atau teks OCR mentah.
Karena itu, penyimpanan yang dijelaskan pada bagian ini merujuk pada
data detail pembelian hasil ekstraksi yang telah divalidasi.

------------------------------------------------------------------------

## 8. Non-Functional Requirements

### 8.1 Security (Keamanan)

1.  Sistem menerapkan autentikasi menggunakan username dan password.
2.  Password pengguna disimpan dalam bentuk hash untuk menjaga keamanan
    data akun.
3.  Hak akses dibedakan antara Pegawai dan Owner sesuai peran
    masing-masing.
4.  Sistem membatasi akses halaman berdasarkan jenis akun yang berhasil
    login.

### 8.2 Performance (Kinerja)

1.  Sistem harus mampu menampilkan data transaksi dan persediaan dengan
    waktu tunggu yang wajar.
2.  Proses penyimpanan transaksi harus berjalan dengan baik dan menjaga
    agar data tidak hilang.
3.  Proses OCR harus dapat menampilkan hasil pembacaan untuk diperiksa
    oleh Pegawai.

### 8.3 Usability (Kemudahan Penggunaan)

1.  Sistem memiliki tampilan yang sederhana dan mudah dipahami.
2.  Navigasi dan menu disusun berdasarkan fungsi serta hak akses
    pengguna.
3.  Formulir input memiliki label dan informasi yang jelas untuk
    mengurangi kesalahan pengisian data.

### 8.4 Compatibility (Kompatibilitas)

1.  Sistem dapat diakses melalui browser pada perangkat desktop maupun
    perangkat dengan ukuran layar lebih kecil.
2.  Tampilan sistem menyesuaikan ukuran layar perangkat yang digunakan.

### 8.5 Reliability (Keandalan)

1.  Sistem harus menjaga konsistensi data antara transaksi penjualan,
    pembelian, dan persediaan.
2.  Sistem harus memberikan notifikasi apabila proses penyimpanan atau
    pengolahan data mengalami kegagalan.
3.  Data transaksi yang telah disimpan harus dapat ditampilkan kembali
    sesuai kebutuhan pengguna.

------------------------------------------------------------------------

## Catatan untuk Finalisasi

Sebelum dokumen ini ditetapkan sebagai PRD final, lakukan pemeriksaan
berikut: - Pastikan tabel `owner` sudah ditambahkan ke database melalui
query SQL. - Pastikan pembaruan stok pembelian dilakukan setelah barang
diterima dan dikonfirmasi, bukan hanya ketika pesanan dibuat atau OCR
dijalankan. - Pastikan fitur pada PRD sama dengan Use Case Diagram dan
implementasi aplikasi. - Pastikan fitur akuntansi dari tabel `coa` dan
`jurnal_umum` tidak diklaim sebagai fitur laporan keuangan apabila belum
termasuk cakupan sistem. - Pastikan kebutuhan tampilan responsif,
keamanan, dan performa disesuaikan dengan implementasi yang benar-benar
dibuat.

**Status dokumen:** Draft PRD yang perlu ditinjau dan diselaraskan
dengan implementasi akhir GudangIn.

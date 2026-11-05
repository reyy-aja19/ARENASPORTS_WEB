# ArenaSports Marketplace Update

Paket ini menambahkan fondasi marketplace multi-venue ke project Laravel yang sudah ada.

## Fitur

1. Venue sebagai toko dan lapangan sebagai produk yang disewakan.
2. Pengguna dapat mengajukan diri menjadi Admin Venue.
3. Superadmin dapat menyetujui atau menolak pengajuan. Persetujuan membuat akun berubah menjadi `venue_admin` dan membuat data venue.
4. Pengguna dapat menjelajahi daftar venue terlebih dahulu, lalu melihat lapangan di venue tersebut.
5. Admin Venue dapat memperbarui profil venue, mengelola lapangan, melihat booking, pembayaran, review, dan laporan milik venue sendiri.
6. Jadwal booking dimuat otomatis berdasarkan tanggal. Slot yang bentrok ditampilkan merah dan slot yang tersedia hijau.
7. Pemesanan multi-jam didukung (1 sampai 8 jam) dan pemeriksaan bentrok dilakukan kembali di dalam transaksi database.
8. Admin Venue dapat meminta penarikan saldo. Superadmin dapat memproses, menandai dibayar, atau menolak permintaan.
9. Dashboard Superadmin menampilkan GMV, estimasi komisi platform 10%, hak pendapatan venue, dan ringkasan penarikan.

## Instalasi aman

1. Backup folder project dan database terlebih dahulu.
2. Ekstrak paket ini sebagai folder project terpisah atau gabungkan perubahan setelah melakukan backup.
3. Periksa konfigurasi `.env` dan pastikan koneksi database mengarah ke database ArenaSports yang benar.
4. Dari folder project jalankan:

```bash
php artisan optimize:clear
php artisan migrate
php artisan route:list
```

Jangan jalankan `php artisan migrate:fresh` pada database yang berisi data penting.

## Alur pengujian

1. Login sebagai akun user.
2. Buka menu `Jadi Admin Venue`, isi nama venue, alamat, kontak, dan jenis olahraga, lalu kirim pengajuan.
3. Logout dan login sebagai Superadmin. Buka menu `Request Admin Venue`, lalu setujui pengajuan.
4. Login kembali menggunakan akun pemohon. Role sudah berubah menjadi `venue_admin`; dashboard Admin Venue hanya mengambil data dengan `owner_admin_id` milik akun tersebut.
5. Buka `Profil Venue`, periksa informasi toko, lalu tambahkan lapangan melalui `Management Lapangan`.
6. Login sebagai user lain, buka `Cari Venue`, pilih venue, lalu pilih lapangan.
7. Uji tanggal dengan booking yang sudah ada. Slot bentrok tampil merah. Uji durasi 2 atau 3 jam dan pastikan waktu selesai mengikuti durasi.
8. Selesaikan pembayaran sandbox Midtrans untuk menghasilkan transaksi lunas.
9. Login sebagai Admin Venue, buka `Saldo & Penarikan`, lalu ajukan penarikan.
10. Login sebagai Superadmin, buka `Penarikan Venue`, proses permintaan, dan tandai dibayar setelah transfer benar-benar dilakukan.

## Catatan keuangan dan pembayaran

- Komisi platform default 10% dari pembayaran yang berstatus berhasil. Nilai ini dapat diubah melalui `ARENASPORTS_VENUE_COMMISSION_PERCENT` pada `.env`; hak pendapatan venue adalah sisa setelah komisi.
- Saldo hanya dihitung dari transaksi yang statusnya berhasil/lunas.
- Pengajuan penarikan belum mengirim uang secara otomatis. Transfer ke rekening Admin Venue masih dilakukan secara manual oleh operator Superadmin, lalu statusnya ditandai `Dibayar`.
- Penarikan dengan status `Menunggu` atau `Diproses` mengurangi saldo yang dapat diajukan kembali. Status `Ditolak` melepaskan kembali saldo tersebut.
- Verifikasi pembayaran tetap mengandalkan notifikasi Midtrans yang valid; perubahan ini tidak mengganti integrasi Midtrans yang sudah ada.

## Catatan pengujian

Pemeriksaan sintaks PHP untuk file yang diubah dan registrasi route Laravel berhasil dijalankan. Blade untuk halaman baru dan layout yang diubah berhasil dikompilasi secara langsung. Migrasi database dan alur pembayaran belum dapat diuji terhadap database produksi dari lingkungan penyusunan paket; jalankan migrasi dan uji alur pada salinan database terlebih dahulu.

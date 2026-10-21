# ArenaSports — Perbaikan Pembayaran Otomatis Midtrans

## Perubahan utama
- Pembayaran baru menggunakan 100% `bookings.total_price`; tidak ada perhitungan DP pada alur booking pengguna.
- Setelah booking dibuat, pengguna langsung diarahkan ke checkout Midtrans.
- Callback server-to-server memvalidasi `signature_key` dengan rumus SHA-512 resmi Midtrans (`order_id + status_code + gross_amount + server key`).
- Notifikasi sukses harus cocok dengan nominal booking dan payment. Status payment/booking menjadi `Lunas`, `paid_at` diisi, dan 10 poin diberikan dalam satu transaksi database.
- `reward_points_awarded` dan `lockForUpdate()` mencegah pemberian poin berulang ketika notifikasi dikirim ulang.
- `pending` tetap menunggu. `deny`, `cancel`, `expire`, dan `failure` tidak pernah dianggap lunas.
- Pembayaran yang sudah lunas tidak diturunkan statusnya oleh notifikasi lama.
- Kegagalan proses webhook dicatat dan mengembalikan HTTP 500 agar Midtrans dapat mencoba ulang.
- Verifikasi manual Admin/Super Admin dinonaktifkan; dashboard pembayaran menunjukkan status otomatis.
- Ringkasan pendapatan bulanan memakai `paid_at`, hanya transaksi lunas.
- Field poin kosong saat edit pengguna tidak lagi mereset saldo menjadi nol.

## Instalasi di Windows / XAMPP
1. Ekstrak ZIP ke folder project yang diinginkan, misalnya `C:\xampp\htdocs\ArenaSports_Completed_Improvements`.
2. Buka Command Prompt di folder `penyewaan_lapangan`.
3. Jalankan:
   ```bat
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
   Jika file `.env` sudah ada di komputer, jangan menimpanya; pertahankan konfigurasi lokal Anda.
4. Buat database MySQL melalui phpMyAdmin, lalu isi `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`.
5. Atur kunci Midtrans Sandbox di `.env`:
   ```env
   MIDTRANS_SERVER_KEY=SB-Mid-server-...
   MIDTRANS_CLIENT_KEY=SB-Mid-client-...
   MIDTRANS_IS_PRODUCTION=false
   ```
   Jangan commit atau membagikan `.env`.
6. Jalankan `php artisan migrate`. Migration `add_reward_points_awarded_to_payments_table` harus berhasil dijalankan.
7. Pastikan endpoint notifikasi publik Midtrans diarahkan ke:
   `https://domain-anda/payments/midtrans/notification`
   Untuk pengembangan lokal, gunakan URL publik HTTPS dari tunnel yang Anda kendalikan. Midtrans tidak dapat mengakses `localhost` secara langsung.
8. Jalankan `php artisan config:clear` lalu `php artisan serve` atau gunakan konfigurasi Apache XAMPP.

## Pemeriksaan
- Pemeriksaan sintaks PHP dijalankan untuk `MidtransController.php`, `UserController.php`, `AdminController.php`, dan `routes/web.php`; semuanya lulus.
- Pengujian pembayaran Sandbox tetap harus dilakukan dengan kredensial dan endpoint notifikasi yang benar-benar dikonfigurasi.
- Tes otomatis Laravel penuh tidak dijalankan di lingkungan pembuat ZIP karena dependency `vendor` sengaja tidak disertakan dan ekstensi PDO SQLite tidak tersedia di lingkungan ini. Setelah `composer install` di komputer Anda, jalankan `php artisan test`.

## Catatan data lama
Nominal pembayaran historis tidak diubah secara massal. Booking baru menyimpan jumlah penuh. Untuk transaksi lama yang memakai DP, lakukan audit sebelum koreksi agar data historis tidak berubah sembarangan.

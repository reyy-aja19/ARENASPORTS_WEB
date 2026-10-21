# ARENASPORTS — Perbaikan Midtrans Sandbox

## Perbaikan yang diterapkan
- Contoh konfigurasi database disamakan dengan dump SQL yang diberikan: `arena_sport` (bukan `arena_sports`).
- Endpoint `POST /payments/midtrans/notification` tetap dikecualikan dari CSRF; signature Midtrans diverifikasi dengan SHA-512.
- Order ID dicari melalui `payments.transaction_id`, lalu relasi `payments.booking_id` digunakan untuk menemukan booking.
- Nominal notifikasi dibandingkan dengan nominal payment dan booking sebelum status diubah.
- `settlement` dan `capture` dengan `fraud_status=accept` mengubah payment dan booking menjadi `Lunas`.
- Status `Lunas` tidak diturunkan oleh notifikasi lama; row locking dan transaksi database mencegah pemrosesan bersamaan merusak status.
- Poin reward diberikan satu kali melalui kolom `reward_points_awarded`.
- Error dicatat ke `storage/logs/laravel.log`; kegagalan pemrosesan mengembalikan HTTP 500 agar Midtrans bisa retry.
- File `.env` lokal tidak disertakan demi melindungi kredensial. Gunakan `.env.example` sebagai template.

## Instalasi / pembaruan
1. Backup database sebelum menjalankan migration.
2. Import `arena_sport.sql` ke MySQL/phpMyAdmin, jika belum dilakukan.
3. Salin `.env.example` menjadi `.env` (jangan menimpa `.env` lokal yang sudah berisi konfigurasi penting).
4. Pastikan `.env` berisi `DB_DATABASE=arena_sport`, username/password MySQL yang benar, dan kredensial Midtrans **Sandbox**.
5. Jalankan:
   ```bash
   composer install
   php artisan key:generate
   php artisan optimize:clear
   php artisan migrate
   php artisan route:list --path=payments/midtrans/notification
   ```
   Jika `.env` yang sudah ada digunakan, jangan jalankan `key:generate` karena akan mengganti APP_KEY yang ada.
6. Pastikan migration `2026_10_14_154501_add_reward_points_awarded_to_payments_table` berhasil. Dump SQL yang diberikan belum mencatat migration ini di tabel `migrations`; karena itu `php artisan migrate` perlu dijalankan pada database yang benar.
7. Atur URL notifikasi di Midtrans Sandbox ke `https://DOMAIN-PUBLIK/payments/midtrans/notification`. Gunakan URL Cloudflare Tunnel aktif dan pastikan tidak terkena challenge browser/WAF.
8. Jalankan `php artisan config:clear` setelah mengubah `.env`.

## Verifikasi
- `php artisan route:list --path=payments/midtrans/notification` berhasil menampilkan route POST.
- Pengujian `php artisan test` di lingkungan pembuat ZIP belum dapat dijalankan karena ekstensi PHP `dom`, `mbstring`, `xml`, dan `xmlwriter` tidak tersedia. Jalankan test di lingkungan PHP lokal yang memenuhi persyaratan Laravel.
- Uji end-to-end tetap memerlukan kredensial Sandbox dan webhook publik yang aktif.

## Diagnostik webhook
Periksa `storage/logs/laravel.log`.
- Tidak ada log: periksa URL Midtrans, Cloudflare Tunnel, dan akses publik.
- Signature invalid: pastikan Server Key Sandbox sesuai.
- Payment tidak ditemukan: pastikan `payments.transaction_id` sama persis dengan `order_id` di Midtrans.
- Nominal tidak cocok: pastikan `payments.amount` dan `bookings.total_price` sesuai dengan `gross_amount`.
- Kolom reward tidak ditemukan: pastikan migration reward di atas sudah dijalankan.

PANDUAN PEMULIHAN ARENASPORTS

Paket ini mempertahankan source code dan data aplikasi. Tidak menjalankan migrate:fresh, db:wipe, atau menghapus tabel.

TEMUAN DARI DATABASE arena_sports.sql
1. Database sudah memiliki tabel venues, venue_applications, venue_withdrawals, venue_application_documents, dan notifications.
2. Riwayat migration berisi migration venue dari 27 Oktober dan migration venue lain dari 2 November. Ini merupakan riwayat versi yang bertumpuk. Jangan menghapus baris pada tabel migrations dan jangan menjalankan migrate:fresh.
3. Migration venue_withdrawals dan notifications dibuat lebih aman agar tidak mencoba membuat tabel yang sudah ada apabila file migration belum tercatat pada tabel migrations.
4. Migration users.email nullable diberi pemeriksaan tabel dan kolom agar tidak gagal bila skema users tidak tersedia.
5. File SQL menunjukkan database memakai MariaDB 10.11.16 dan dump dibuat dengan PHP 8.3.36. Project memakai Laravel 12.56.0 dan membutuhkan PHP 8.2 atau lebih baru.

LANGKAH WINDOWS / XAMPP
1. Hentikan php artisan serve.
2. Backup folder project dan export database arena_sports melalui phpMyAdmin. Jangan lewati langkah ini.
3. Ekstrak paket ini ke folder project, misalnya C:\xampp\htdocs\penyewaan_lapangan. Jangan menimpa file .env lokal dengan .env.example.
4. Buka CMD pada folder project.
5. Pastikan file .env berisi DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=arena_sports, DB_USERNAME=root, dan DB_PASSWORD sesuai konfigurasi MariaDB lokal. Pertahankan APP_KEY yang sedang dipakai.
6. Jalankan perintah berikut satu per satu:

   php -v
   php -m | findstr /I "pdo_mysql mbstring openssl fileinfo tokenizer xml dom ctype json"
   composer install
   php artisan optimize:clear
   php artisan migrate:status

7. Periksa output migrate:status. Migration yang sudah berstatus Ran tidak perlu dijalankan ulang. Jika hanya 2026_11_04_040000_make_user_email_nullable_for_phone_auth yang Pending, jalankan php artisan migrate. Jangan jalankan migrate:fresh.
8. Jalankan php artisan route:list
9. Jalankan php artisan serve --host=127.0.0.1 --port=8000

JIKA MASIH HTTP 500
Buka CMD kedua pada folder project dan jalankan:
   type storage\logs\laravel.log

Kirimkan bagian paling bawah yang memuat exception pertama dan stack trace. Jangan kirim isi .env, APP_KEY, password database, atau credential Midtrans.

CATATAN PENTING
Audit paket ini dapat memeriksa sintaks PHP dan pemetaan route. Pengujian database langsung tidak dapat dilakukan pada lingkungan pembuatan paket karena driver PDO MySQL tidak tersedia. Karena itu, keberhasilan koneksi database dan migrasi harus diverifikasi pada XAMPP pengguna melalui migrate:status dan migrate.

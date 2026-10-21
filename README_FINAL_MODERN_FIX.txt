FINAL MODERN UI/UX FIX - Sports Field Rental Admin

Perbaikan utama:
1. Dashboard grafik tidak lagi melebar/terlalu tinggi.
2. Chart dibungkus .chart-wrap dan Chart.js memakai maintainAspectRatio:false.
3. Notifikasi bell memakai klik, bukan hover, sehingga tidak ketutupan/tertutup sendiri.
4. Z-index topbar dan notification panel diperbaiki.
5. UI dashboard dibuat lebih modern: hero overview, card hover, tombol animasi, tabel lebih interaktif.
6. Export CSV/Excel tetap hanya berada di menu Export Laporan.
7. Upload foto lapangan dan foto admin tetap memakai public/uploads agar mudah tampil di lokal.
8. .env.example sudah disetel CACHE_STORE=file dan SESSION_DRIVER=file untuk menghindari error table cache/sessions.

Cara menjalankan:
1. Extract zip ke C:\xampp\htdocs\penyewaan_lapangan_framework
2. Buka terminal di folder project
3. Jalankan:
   composer install
   copy .env.example .env
   php artisan key:generate

4. Pastikan .env berisi:
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   SESSION_DRIVER=file
   CACHE_STORE=file

5. Jika database lama error/bentrok, hapus file:
   database/database.sqlite
   lalu buat ulang:
   type nul > database\database.sqlite

6. Jalankan:
   php artisan migrate:fresh --seed
   php artisan optimize:clear
   php artisan serve

Login:
Email: admin@gmail.com
Password: admin123

URL:
http://127.0.0.1:8000/login

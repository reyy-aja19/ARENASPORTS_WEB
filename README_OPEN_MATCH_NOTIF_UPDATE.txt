UPDATE: Open Match + Notifikasi Scroll

Perubahan utama:
1. Menambahkan menu sidebar "Open Match".
2. Menambahkan halaman admin /admin/open-match untuk tambah, edit, ubah status, dan hapus open match.
3. Menambahkan tabel database open_matches.
4. Menambahkan model App\Models\OpenMatch.
5. Menambahkan seed data contoh open match.
6. Memperbaiki scroll notifikasi supaya bisa naik-turun ketika daftar notifikasi banyak.

Cara menjalankan setelah extract:
1. composer install
2. copy .env.example .env
3. php artisan key:generate
4. pastikan .env memakai:
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   SESSION_DRIVER=file
   CACHE_STORE=file
5. Jika memakai database baru:
   del database\database.sqlite
   type nul > database\database.sqlite
   php artisan migrate:fresh --seed
6. php artisan optimize:clear
7. php artisan serve
8. Buka http://127.0.0.1:8000/login

Login:
email: admin@gmail.com
password: admin123

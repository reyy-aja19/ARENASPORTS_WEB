UPDATE FITUR:
1. Search global di topbar sekarang memfilter data di halaman aktif tanpa reload.
2. Management Lapangan sekarang punya slide foto dan fasilitas.
   - Tambah/edit lapangan bisa upload foto utama dan banyak foto slide.
   - Fasilitas bisa dipilih lewat checkbox.
3. Open Match diperbarui dengan tampilan lebih mirip rancangan mobile:
   - filter/cari match
   - form buat open match
   - card match dengan status, waktu, lokasi, progress slot, edit, ubah status, hapus.
4. Notifikasi dropdown bisa scroll naik-turun ketika data banyak.

CARA JALAN:
composer install
copy .env.example .env
php artisan key:generate

Pastikan .env:
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

Jika pakai database baru:
del database\database.sqlite
type nul > database\database.sqlite
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan serve

Login:
admin@gmail.com
admin123

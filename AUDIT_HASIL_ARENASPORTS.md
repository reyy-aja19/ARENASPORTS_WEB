# Laporan Audit dan Perbaikan ArenaSports

Tanggal pemeriksaan: 5 November 2026

## Ruang lingkup dan batasan

Pemeriksaan dilakukan terhadap arsip project `penyewaan_lapangan` yang diunggah. Project tidak dibuat ulang dan database tidak diubah. Tidak ada perintah `migrate:fresh`, `db:wipe`, atau penghapusan tabel yang dijalankan.

Pemeriksaan ini merupakan audit statis dan pengujian yang dapat dijalankan di lingkungan pemeriksaan. Ini bukan sertifikasi bahwa seluruh alur aplikasi telah lolos uji end-to-end.

## Perubahan kode

1. `config/app.php`
   - Sebelumnya cipher ditulis tetap sebagai `AES-256-CBC`.
   - Sekarang membaca `APP_CIPHER` dengan default `AES-256-CBC`.
   - APP_KEY tidak dibuat ulang dan tidak diganti.
2. `.env.example`
   - Menambahkan `APP_CIPHER=AES-256-CBC`.
   - Tidak memuat kredensial layanan.

## Temuan audit

### Konfigurasi encryption

- File `.env` pada arsip asli memiliki APP_KEY.
- Bentuk Base64 valid dan hasil decode berukuran 32 byte, sesuai AES-256-CBC.
- Uji encrypt/decrypt lokal terhadap nilai uji berhasil.
- Tidak ditemukan `bootstrap/cache/config.php` pada arsip yang diperiksa.
- APP_CIPHER sebelumnya tidak digunakan oleh `config/app.php`; hal ini telah diperbaiki.
- File `.env` asli sengaja tidak disertakan dalam ZIP distribusi. Simpan salinan lokal secara aman. Jangan mengganti APP_KEY pada instalasi yang sudah memiliki data/session terenkripsi.

### Backend dan routing

- 80 file PHP pada `app`, `bootstrap`, `config`, `database`, `routes`, dan `tests` diperiksa dengan `php -l`: tidak ada syntax error.
- `php artisan route:list --json` berhasil dan mendaftarkan 108 route.
- Tidak ditemukan pasangan method + URI route yang duplikat.
- Pemeriksaan refleksi menemukan semua action controller yang direferensikan route memiliki class dan method.
- Tidak ditemukan file `routes/api.php`; routing yang ada menggunakan `routes/web.php`.
- Middleware `CekLogin` memuat ulang user dari database, memeriksa status, dan menyelaraskan role sesi.
- Route user dan admin menggunakan middleware role.
- Controller admin memiliki pemeriksaan pemilik untuk lapangan dan booking.
- Controller notifikasi membatasi akses notifikasi ke user pemiliknya.

### Booking, pembayaran, reward, multi-venue

- Booking melakukan pemeriksaan bentrok dan mengulang pemeriksaan di dalam transaksi dengan `lockForUpdate` pada baris lapangan.
- Pembayaran Midtrans memvalidasi signature notifikasi, meminta status transaksi ke API Midtrans, dan mencocokkan nominal.
- Pembayaran sukses memperbarui payment dan booking dalam transaksi.
- Penanda `reward_points_awarded` digunakan untuk mencegah pemberian poin berulang.
- Penukaran reward mengunci saldo user dan mengurangi poin di dalam transaksi.
- Pembatasan akses multi-venue ditemukan pada controller admin; pengujian dinamis antar akun belum dilakukan.

### Temuan yang belum diperbaiki / perlu keputusan bisnis

1. Endpoint ketersediaan di `MarketplaceController::availability` masih mengirim jam operasional tetap `07:00` sampai `23:00`. Nilai `venues.operating_hours` belum digunakan di endpoint tersebut.
2. Pemeriksaan booking di `UserController::bookingStore` belum memvalidasi jam booking terhadap jam operasional venue. Format `operating_hours` pada data belum cukup distandardisasi untuk mengubah aturan ini tanpa risiko mengubah perilaku bisnis.
3. Implementasi OTP WhatsApp ditemukan. Tidak ditemukan alur OTP email di controller dan route yang diperiksa.
4. Kolom `users.phone` belum memiliki unique constraint pada migration dasar. Controller memeriksa nomor ganda, tetapi perlindungan race-condition lintas request memerlukan audit data dan migration tambahan yang aman.
5. Pengujian akses silang antarvenue, bentrok booking paralel, dan webhook sungguhan membutuhkan database serta layanan eksternal.

### Migration

- Ditemukan 25 file migration.
- Migration `2026_11_04_040000_make_user_email_nullable_for_phone_auth` mengubah kolom email menjadi nullable; rollback mengisi placeholder unik untuk baris yang email-nya null sebelum menjadikan kolom wajib.
- Migration venue dan dokumen menggunakan pemeriksaan keberadaan tabel/kolom pada beberapa langkah.
- Tidak ada migration yang dijalankan atau diubah dalam audit ini.
- Status migration database belum dapat dibaca karena ekstensi PDO MySQL tidak tersedia pada lingkungan pemeriksaan.

## Hasil pengujian lingkungan

| Pengujian | Hasil |
|---|---|
| PHP syntax 80 file | LULUS |
| Laravel version | Laravel 12.56.0 terdeteksi |
| Artisan route:list | LULUS, 108 route |
| Route duplikat | Tidak ditemukan |
| Controller action route | Tidak ada yang hilang |
| Encryption round-trip | LULUS |
| Composer validate | BELUM DIUJI: Composer tidak terpasang |
| PHPUnit / artisan test | BELUM DIUJI: ekstensi DOM, mbstring, XML, XMLWriter tidak tersedia |
| Artisan about | Gagal karena ekstensi mbstring tidak tersedia |
| Migration status | BELUM DIUJI: PDO MySQL driver tidak tersedia |
| Vite build | BELUM DIUJI: node_modules arsip tidak lengkap untuk platform Linux; Rollup native optional dependency tidak ditemukan |
| MySQL end-to-end | BELUM DIUJI: tidak ada PDO MySQL/layanan DB yang dapat dipakai |
| Midtrans / Google / Fonnte | BELUM DIUJI: tidak dilakukan panggilan ke layanan eksternal |

Lingkungan pemeriksaan menggunakan PHP 8.4.24 dan Node.js 22.16.0, bukan target PHP 8.3 Windows/XAMPP.

## File yang diubah

- `config/app.php`
- `.env.example`

## File yang ditambahkan

- `AUDIT_HASIL_ARENASPORTS.md`

## File yang tidak disertakan dalam ZIP distribusi

- `.env`: berisi konfigurasi rahasia lokal.
- `node_modules/`: dependensi platform-spesifik; instal ulang dengan `npm ci`.
- `storage/logs/` dan cache view hasil runtime: tidak diperlukan untuk distribusi dan dapat mengandung data diagnostik.
- Cache runtime Laravel.

Folder `vendor/` dan file lock Composer tetap dipertahankan dari arsip sumber. Jika instalasi lokal mengalami ketidakcocokan dependency, jalankan `composer install`.

## Langkah Windows CMD yang aman

1. Backup folder project dan database `arena_sports` terlebih dahulu.
2. Ekstrak ZIP hasil audit ke `C:\xampp\htdocs\penyewaan_lapangan`.
3. Salin `.env.example` menjadi `.env` hanya jika belum ada `.env` lokal. Jika sudah ada, pertahankan `.env` lokal dan perbarui `APP_CIPHER` bila diperlukan.
4. Pastikan APP_KEY lama tetap dipertahankan untuk database/session yang sudah berjalan. Jangan jalankan `php artisan key:generate` pada instalasi existing tanpa memahami dampaknya.
5. Buka XAMPP Control Panel, jalankan MySQL. Apache hanya diperlukan bila memakai Apache; `php artisan serve` memakai server PHP sendiri.
6. Buka CMD pada folder project, jalankan `composer install` bila vendor tidak cocok, lalu `npm ci` bila ingin membangun ulang aset.
7. Jalankan `php artisan config:clear`.
8. Jalankan `php artisan migrate:status` terlebih dahulu. Periksa daftar migration sebelum menjalankan `php artisan migrate`. Jangan jalankan `migrate:fresh` atau `db:wipe`.
9. Jalankan `php artisan serve --host=127.0.0.1 --port=8000`.
10. Jika memakai Vite development server, buka CMD kedua di folder project dan jalankan `npm run dev`. Untuk aset produksi, jalankan `npm run build`.

Perintah database migration hanya dilakukan setelah backup dan pemeriksaan status. Audit ini tidak mengubah database.

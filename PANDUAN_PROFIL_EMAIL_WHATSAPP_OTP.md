# Update ArenaSports: Profil, Email Activity, dan WhatsApp OTP

Pembaruan ini menambahkan:
- Upload, pratinjau, ganti, dan hapus foto profil user.
- Edit nama, email, nomor WhatsApp, dan password opsional.
- Notifikasi email saat registrasi email/password, login email/password, login Google, dan pendaftaran Google.
- Login/daftar khusus user menggunakan OTP WhatsApp melalui Fonnte.
- Notifikasi aktivitas login/daftar melalui WhatsApp jika nomor user tersedia dan Fonnte aktif.
- Akun yang dibuat hanya dengan nomor WhatsApp dapat memiliki email `NULL`; migrasi tambahan mengubah kolom email menjadi nullable tanpa menghapus data.

## File konfigurasi yang wajib disiapkan

### 1. Gmail SMTP

Buat Google App Password di akun Gmail yang akan menjadi pengirim (aktifkan 2-Step Verification terlebih dahulu). Jangan gunakan password login Gmail biasa.

Tambahkan ke `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=alamatgmailpengirim@gmail.com
MAIL_PASSWORD=APP_PASSWORD_GMAIL_16_DIGIT
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=alamatgmailpengirim@gmail.com
MAIL_FROM_NAME="ArenaSports"
```

Notifikasi dikirim ke email yang terdaftar pada akun user. Untuk akun Google, notifikasi dikirim ke email yang diberikan Google. Jika `MAIL_MAILER=log`, email hanya masuk ke log Laravel dan tidak terkirim ke Gmail.

### 2. WhatsApp OTP dengan Fonnte

Daftar/aktifkan akun Fonnte, hubungkan nomor WhatsApp pengirim sesuai petunjuk Fonnte, lalu salin token API. Tambahkan ke `.env`:

```env
FONNTE_TOKEN=TOKEN_API_FONNTE_KAMU
FONNTE_ENDPOINT=https://api.fonnte.com/send
```

Token API adalah rahasia. Jangan unggah `.env` ke GitHub dan jangan kirim token melalui chat. Nomor user harus aktif di WhatsApp dan ditulis misalnya `081234567890` atau `6281234567890`.

### 3. Google OAuth

Login Google tetap menggunakan Socialite yang sudah ada. Pastikan `.env` memuat:

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

URL callback yang sama harus didaftarkan di Google Cloud Console. Untuk domain produksi, ganti dengan URL HTTPS domain tersebut.

## Cara menerapkan pada project Windows/XAMPP

1. Backup folder project dan database terlebih dahulu.
2. Salin file pembaruan ke project yang digunakan (atau gunakan folder hasil ekstrak ZIP ini sebagai salinan project baru).
3. Pertahankan `.env` milik project sendiri. Jangan menimpa `.env` dengan file contoh.
4. Atur nilai Gmail, Fonnte, dan Google OAuth di `.env`.
5. Jalankan CMD pada folder project:

```bat
php artisan optimize:clear
php artisan migrate
php artisan storage:link
php artisan route:list --path=whatsapp -v
php artisan serve
```

Migrasi baru hanya membuat kolom `users.email` dapat bernilai NULL agar pendaftaran nomor WhatsApp tanpa email bisa disimpan. Migrasi ini tidak menghapus tabel atau data. Jangan menjalankan `migrate:fresh` atau `db:wipe`.

Jika `storage:link` sudah pernah dibuat, Laravel mungkin memberi pesan bahwa link sudah ada; itu tidak perlu dibuat ulang. Foto baru disimpan pada `storage/app/public/profiles`.

## Alur penggunaan

- Login email/password: user login seperti biasa; email notifikasi aktivitas dikirim ke email akun.
- Daftar email/password: user mengisi email asli; email notifikasi pendaftaran dikirim ke alamat itu.
- Google: user menekan tombol Google; notifikasi login/pendaftaran dikirim ke email Google.
- Login WhatsApp: masukkan nomor terdaftar, terima OTP 6 digit di WhatsApp, lalu verifikasi.
- Daftar WhatsApp: isi nama dan nomor, verifikasi OTP, lalu akun user dibuat tanpa email. Email dapat ditambahkan nanti dari Profil.
- Login/daftar WhatsApp akan mengirim notifikasi aktivitas ke WhatsApp yang sama. Jika email sudah diisi pada akun, notifikasi email juga dikirim.
- Profil: user dapat mengubah foto, nama, email, nomor WhatsApp, dan password.

## Catatan pengujian

Kode PHP dan route sudah diperiksa secara statis. Pengiriman Gmail dan WhatsApp membutuhkan kredensial layanan yang valid dan harus diuji dari komputer pemilik project. Google OAuth juga memerlukan client ID/secret yang valid. Jangan anggap notifikasi eksternal berhasil sebelum melakukan uji nyata.

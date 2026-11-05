# ArenaSports — Update Public Access, Notifications, and Login UI

## Ringkasan perubahan

- Marketplace venue, daftar lapangan, detail lapangan, ketersediaan jadwal, dan Open Match informasi publik dapat dibuka tanpa login.
- Proses booking, checkout, profil, pembayaran, reward, pengajuan venue, dan aktivitas yang mengubah data tetap dilindungi `CekLogin` serta role `user`.
- Request booking tanpa login diarahkan ke login. Data tanggal, jam, durasi, dan metode pembayaran disimpan sementara di session lalu dipulihkan pada detail lapangan setelah login.
- Redirect setelah login mendukung URL internal yang sebelumnya diminta; Admin/Superadmin tetap diarahkan ke dashboard sesuai role.
- Dropdown notifikasi, badge belum dibaca, halaman riwayat dengan pagination, tandai dibaca, dan tandai semua dibaca ditambahkan.
- Notifikasi database diintegrasikan ke pembuatan booking, status Midtrans berhasil/gagal/kedaluwarsa/dibatalkan, konfirmasi pembayaran Cash, Open Match dibuat/diikuti/dibatalkan, dan penukaran reward.
- Callback Midtrans tetap memverifikasi signature, meminta status transaksi ke API Midtrans, mencocokkan nominal, dan memakai row lock/idempotency agar poin/status/notifikasi tidak diproses berulang.
- Halaman login diperbarui dengan tampilan responsif, branding ArenaSports, tombol tampil/sembunyikan password, Google Login, dan pesan status.

## Penting sebelum mengganti folder project

1. Backup folder project lama.
2. Export database yang sedang dipakai melalui phpMyAdmin. Jangan menghapus database lama.
3. Extract ZIP hasil update ke folder baru untuk pengujian terlebih dahulu.
4. Salin file `.env` dari project lama ke root folder hasil extract. File `.env` tidak disertakan dalam ZIP ini untuk melindungi kredensial database, Midtrans, dan OAuth.
5. Pastikan `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_URL`, konfigurasi Midtrans, dan Google OAuth tetap menggunakan nilai project milikmu.
6. Folder `vendor` sudah disertakan. Jika dependensi perlu diselaraskan, jalankan `composer install`.

## Perintah CMD

Jalankan dari folder project hasil extract:

```cmd
php artisan optimize:clear
php artisan route:list --path=user -v
php artisan serve
```

Migration notifikasi sudah tercatat `Ran` pada hasil `php artisan migrate:status` yang diberikan pemilik project. Karena itu, untuk database tersebut **tidak perlu membuat atau menjalankan migration notifikasi baru**. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau menghapus database.

Jika memakai database baru yang memang belum pernah dimigrasikan, jalankan `php artisan migrate` setelah `.env` menunjuk database yang benar. Perintah ini menjalankan migration yang masih pending saja; tetap backup database terlebih dahulu.

## URL untuk pengujian

- `/` — homepage publik
- `/user/venue` — daftar venue publik
- `/user/venue/{id}` — detail venue publik
- `/user/lapangan` — daftar lapangan publik
- `/lapangan/{id}` — detail lapangan publik dan form booking
- `/user/open-match` — informasi Open Match publik
- `/user/notifikasi` — riwayat notifikasi (wajib login sebagai User)

## Skenario uji manual

1. Buka homepage, venue, lapangan, detail venue, detail lapangan, dan Open Match dalam mode incognito.
2. Isi tanggal, jam, durasi, dan metode pembayaran pada detail lapangan lalu tekan Booking. Pastikan diarahkan ke login dengan pesan: “Silakan login terlebih dahulu untuk melakukan booking lapangan.”
3. Login sebagai User. Pastikan kembali ke detail lapangan yang diminta dan nilai form yang sudah dipilih masih terisi. Tekan Booking lagi untuk melanjutkan.
4. Pastikan user hanya melihat notifikasi miliknya, badge unread berubah setelah notifikasi dibaca, dan tombol tandai semua dibaca bekerja.
5. Uji pembayaran Sandbox Midtrans yang berhasil, gagal, kedaluwarsa, dan dibatalkan. Pastikan callback masuk ke `/payments/midtrans/notification` dan status diverifikasi dari API Midtrans.
6. Kirim callback sukses yang sama lebih dari sekali. Pastikan poin hanya bertambah sekali dan notifikasi sukses tidak terduplikasi.
7. Uji Open Match dibuat, pemain lain bergabung, dan admin membatalkan/menghapus Open Match.
8. Uji penukaran reward dan konfirmasi pembayaran Cash oleh Admin Venue.
9. Login sebagai Admin Venue dan Superadmin. Pastikan keduanya tetap masuk ke dashboard admin dan route admin tetap terlindungi role.

## Catatan verifikasi

- File PHP yang diubah diperiksa dengan `php -l`.
- Route berhasil dimuat menggunakan `php artisan route:list` dan route publik/protected diperiksa middleware-nya.
- Blade template yang diubah berhasil dikompilasi melalui Blade compiler.
- Pengujian PHPUnit penuh tidak dapat dijalankan di lingkungan build ini karena ekstensi PHP `dom`, `mbstring`, `xml`, dan `xmlwriter` tidak tersedia. Jalankan pengujian di PHP XAMPP yang sudah mengaktifkan ekstensi tersebut.
- Pengujian transaksi nyata terhadap database dan callback Midtrans Sandbox harus dilakukan pada mesin project pemilik karena ZIP ini tidak membawa database runtime.

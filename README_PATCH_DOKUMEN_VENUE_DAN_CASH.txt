PATCH ARENASPORTS: PENGAJUAN VENUE DETAIL DAN PEMBAYARAN CASH

ISI PERUBAHAN
1. Form pengajuan Admin Venue diperluas dengan:
   - identitas pemohon dan nomor WhatsApp pemohon;
   - nama, alamat, nomor kontak, deskripsi, jenis olahraga;
   - jam operasional dan tautan Google Maps;
   - status kepemilikan tempat (milik sendiri atau sewa);
   - unggah KTP/identitas;
   - bukti kepemilikan atau perjanjian sewa sesuai status tempat;
   - NIB/izin usaha sebagai dokumen opsional;
   - foto tampak depan, bagian dalam, dan fasilitas lapangan.
2. Dokumen disimpan di disk local private, bukan folder public.
   Unduhan dokumen hanya dapat dilakukan oleh Superadmin melalui route terproteksi.
3. Superadmin dapat memeriksa dokumen, menyetujui, atau menolak pengajuan
   dengan catatan penolakan.
4. Data jam operasional dan Google Maps disimpan ke profil venue.
5. Form booking menyediakan Midtrans atau Cash / Bayar di Tempat.
6. Pembayaran Cash tetap berstatus Menunggu sampai Admin Venue mengonfirmasi
   uang diterima pada menu Admin > Pembayaran.
7. Pembayaran Midtrans tetap menggunakan checkout dan notifikasi Midtrans.
   Endpoint checkout Midtrans menolak booking yang menggunakan metode Cash.

CARA MEMASANG
1. Buat salinan folder proyek dan backup database terlebih dahulu.
2. Karena arsip memiliki folder utama penyewaan_lapangan, ekstrak ZIP ke
   folder induk proyek (pada instalasi kamu: C:\\xampp\\htdocs), lalu izinkan
   file patch menimpa file dengan nama yang sama.
3. File .env dan folder .git tidak disertakan dalam arsip ini. Pertahankan
   .env lokal yang sekarang digunakan.
4. Dari terminal pada folder proyek, jalankan:
      php artisan optimize:clear
      php artisan migrate
      php artisan route:list
5. Jangan menjalankan php artisan migrate:fresh karena dapat menghapus data.
6. Pastikan PHP upload_max_filesize dan post_max_size cukup untuk unggahan
   dokumen. Form membatasi setiap file hingga 5 MB.
7. Login sebagai Superadmin untuk membuka Admin > Request Admin Venue.
   Dokumen hanya bisa diunduh melalui halaman tersebut.
8. Untuk menguji Cash:
   - login sebagai User;
   - pilih lapangan dan jadwal;
   - pilih Cash / Bayar di Tempat;
   - periksa transaksi pada Riwayat Booking;
   - login sebagai Admin Venue pemilik lapangan;
   - buka Admin > Pembayaran lalu tekan Konfirmasi Cash;
   - status pembayaran dan booking berubah menjadi Lunas.

CATATAN
- Migration baru bernama 2026_11_02_000004_add_detailed_venue_application_documents.php.
- Migration menambahkan kolom yang belum ada dan membuat tabel
  venue_application_documents. Migration tidak menghapus data lama.
- Dokumen KTP dan bukti kepemilikan bersifat sensitif. Jangan memindahkannya
  ke public/uploads atau membuat tautan publik.
- Pemeriksaan yang dilakukan pada paket ini mencakup pemeriksaan sintaks PHP
  (php -l) pada seluruh file PHP yang diubah. Pengujian integrasi database,
  unggah file pada server lokal, dan transaksi Midtrans live tetap harus
  dilakukan pada lingkungan proyek pengguna.

# Panduan Tanda Tangan Jurnal

Dokumen ini menjelaskan cara kerja sistem tanda tangan jurnal dan langkah-langkah untuk menandatangani jurnal sebagai guru maupun kepala sekolah.

## Cara Kerja Sistem Tanda Tangan

Sistem tanda tangan jurnal memiliki dua tingkat penandatanganan:

1. **Tanda Tangan Guru (Pemilik Jurnal)**: Setiap jurnal harus ditandatangani oleh guru yang membuat jurnal tersebut
2. **Tanda Tangan Kepala Sekolah**: Setelah ditandatangani oleh guru, jurnal dapat ditandatangani oleh kepala sekolah sebagai validasi

### Status Tanda Tangan

Pada halaman daftar jurnal, Anda dapat melihat status tanda tangan dengan indikator berikut:

- 🔴 **Belum Ditandatangani** (Badge merah dengan ikon X)
- 🟢 **Sudah Ditandatangani** (Badge hijau dengan ikon centang)

### Widget Statistik Tanda Tangan

Di bagian atas halaman daftar jurnal, terdapat widget yang menampilkan:

- **Total Jurnal**: Jumlah total jurnal yang Anda buat
- **Belum Ditandatangani Guru**: Jumlah jurnal yang belum Anda tandatangani sebagai guru
- **Sudah Ditandatangani Guru**: Jumlah jurnal yang sudah Anda tandatangani sebagai guru
- **Belum Ditandatangani Kepala Sekolah**: Jumlah jurnal yang belum ditandatangani oleh kepala sekolah
- **Sudah Ditandatangani Kepala Sekolah**: Jumlah jurnal yang sudah ditandatangani oleh kepala sekolah

Widget ini akan otomatis diperbarui setiap 10 detik untuk menampilkan data terkini.

## Cara Menandatangani Jurnal sebagai Guru

### Menandatangani Satu Jurnal

1. Buka halaman **Jurnal** dari menu navigasi
2. Pada daftar jurnal, cari jurnal yang ingin ditandatangani
3. Klik tombol **Edit** (ikon pensil) pada jurnal tersebut
4. Di halaman edit jurnal, scroll ke bagian **Tanda Tangan**
5. Klik pada area tanda tangan untuk mulai menandatangani
6. Gunakan mouse atau touchpad untuk membuat tanda tangan Anda
7. Jika ingin mengulangi, klik tombol **Clear** untuk menghapus tanda tangan
8. Setelah selesai, klik tombol **Simpan** untuk menyimpan jurnal beserta tanda tangan

### Menandatangani Banyak Jurnal Sekaligus (Bulk Sign)

Fitur ini memungkinkan Anda menandatangani beberapa jurnal sekaligus dengan tanda tangan yang sama:

1. Buka halaman **Jurnal** dari menu navigasi
2. Centang kotak di sebelah kiri jurnal-jurnal yang ingin ditandatangani
3. Klik tombol **Bulk Actions** di bagian atas tabel
4. Pilih **Tandatangani sebagai Pemilik (Bulk)**
5. Akan muncul modal konfirmasi yang menampilkan jumlah jurnal yang akan ditandatangani
6. Buat tanda tangan Anda pada area yang disediakan
7. Klik tombol **Tandatangani** untuk menandatangani semua jurnal yang dipilih
8. Sistem akan menampilkan notifikasi berhasil atau gagal untuk setiap jurnal

> **Catatan**: Anda hanya dapat menandatangani jurnal yang Anda buat sendiri. Jika ada jurnal yang bukan milik Anda dalam pilihan, sistem akan melewatinya dan menampilkan peringatan.

## Cara Menandatangani Jurnal sebagai Kepala Sekolah

### Menandatangani Satu Jurnal

1. Buka halaman **Jurnal** dari menu navigasi
2. Pada daftar jurnal, cari jurnal yang ingin ditandatangani
3. Klik tombol **Edit** (ikon pensil) pada jurnal tersebut
4. Di halaman edit jurnal, scroll ke bagian **Tanda Tangan Kepala Sekolah**
5. Klik pada area tanda tangan untuk mulai menandatangani
6. Gunakan mouse atau touchpad untuk membuat tanda tangan Anda
7. Jika ingin mengulangi, klik tombol **Clear** untuk menghapus tanda tangan
8. Setelah selesai, klik tombol **Simpan** untuk menyimpan tanda tangan

### Menandatangani Banyak Jurnal Sekaligus (Bulk Sign)

Fitur ini khusus tersedia untuk kepala sekolah:

1. Buka halaman **Jurnal** dari menu navigasi
2. Centang kotak di sebelah kiri jurnal-jurnal yang ingin ditandatangani
3. Klik tombol **Bulk Actions** di bagian atas tabel
4. Pilih **Tandatangani sebagai Kepala Sekolah (Bulk)**
5. Akan muncul modal konfirmasi yang menampilkan jumlah jurnal yang akan ditandatangani
6. Buat tanda tangan Anda pada area yang disediakan
7. Klik tombol **Tandatangani** untuk menandatangani semua jurnal yang dipilih
8. Sistem akan menampilkan notifikasi berhasil atau gagal untuk setiap jurnal

> **Catatan**: Fitur bulk sign kepala sekolah hanya muncul jika Anda memiliki role "headmaster" di sistem.

## Tips dan Catatan Penting

1. **Tanda Tangan Digital**: Tanda tangan yang dibuat akan disimpan sebagai gambar digital dan tidak dapat diubah setelah disimpan
2. **Urutan Tanda Tangan**: Sebaiknya guru menandatangani jurnal terlebih dahulu sebelum kepala sekolah menandatangani
3. **Tanda Tangan Ulang**: Jika Anda menandatangani jurnal yang sudah pernah ditandatangani, tanda tangan lama akan diganti dengan yang baru
4. **Validasi**: Sistem akan memvalidasi bahwa:
   - Guru hanya dapat menandatangani jurnal miliknya sendiri
   - Kepala sekolah harus memiliki role "headmaster" untuk dapat menandatangani
5. **Polling Otomatis**: Halaman daftar jurnal akan otomatis refresh setiap 10 detik untuk menampilkan status tanda tangan terbaru

## Troubleshooting

### Tidak Bisa Menandatangani Jurnal

- Pastikan Anda adalah pemilik jurnal (untuk tanda tangan guru)
- Pastikan Anda memiliki role "headmaster" (untuk tanda tangan kepala sekolah)
- Periksa koneksi internet Anda

### Tanda Tangan Tidak Tersimpan

- Pastikan Anda telah membuat tanda tangan sebelum klik tombol Simpan
- Pastikan tidak ada error di browser (cek console browser dengan F12)
- Coba refresh halaman dan ulangi proses

### Bulk Sign Gagal

- Periksa notifikasi yang muncul untuk mengetahui jurnal mana yang gagal
- Pastikan semua jurnal yang dipilih adalah milik Anda (untuk guru)
- Pastikan jurnal yang dipilih belum ditandatangani sebelumnya

## Kontak Dukungan

Jika mengalami masalah atau memiliki pertanyaan lebih lanjut, silakan hubungi administrator sistem.

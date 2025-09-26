# Panduan Deployment di Shared Hosting

## Masalah File Word Corrupt

Jika Anda mengalami masalah file Word yang corrupt saat di-deploy di shared hosting, ikuti panduan berikut:

### Penyebab Umum

1. **Output Buffer Issues**: Shared hosting sering memiliki konfigurasi output buffering yang berbeda
2. **Memory Limitations**: Shared hosting memiliki batasan memory yang lebih ketat
3. **PHP Configuration**: Konfigurasi PHP yang berbeda antara local dan production
4. **File Path Issues**: Path file yang berbeda di shared hosting
5. **Invalid Filename Characters**: Karakter slash (/ \) dan karakter khusus lainnya dalam nama file

### Solusi yang Telah Diimplementasikan

#### 1. Perbaikan Output Buffer
- Membersihkan output buffer sebelum generate file
- Mematikan error reporting sementara saat generate file
- Menambahkan header cache control yang lebih ketat

#### 2. Validasi Gambar yang Lebih Ketat
- Validasi ukuran file (maksimal 5MB)
- Validasi dimensi gambar (maksimal 4000x4000 pixel)
- Penanganan error yang lebih robust

#### 3. Pembersihan Nama File
- Menghapus karakter tidak valid dari nama file (/ \ : * ? " < > |)
- Membersihkan nama mata pelajaran dan tahun akademik
- Logging untuk debugging masalah filename

#### 4. Method Alternatif untuk Shared Hosting
- Menyimpan file ke temporary storage terlebih dahulu
- Menggunakan Laravel's download response
- Automatic cleanup file temporary

### Cara Mengaktifkan Method Alternatif

1. **Tambahkan ke file `.env`:**
   ```
   USE_ALTERNATIVE_DOWNLOAD=true
   ```

2. **Atau set langsung di config/app.php:**
   ```php
   'use_alternative_download' => true,
   ```

### Troubleshooting

#### Jika File Masih Corrupt:

1. **Periksa Log Laravel:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Periksa Memory Usage:**
   - Pastikan memory_limit PHP minimal 256M
   - Monitor penggunaan memory saat generate file

3. **Periksa PHP Configuration:**
   ```php
   // Tambahkan di awal controller untuk debugging
   Log::info('PHP Config', [
       'memory_limit' => ini_get('memory_limit'),
       'max_execution_time' => ini_get('max_execution_time'),
       'output_buffering' => ini_get('output_buffering'),
       'error_reporting' => error_reporting()
   ]);
   ```

4. **Test dengan Data Minimal:**
   - Coba download jurnal tanpa gambar terlebih dahulu
   - Jika berhasil, masalah kemungkinan di processing gambar

#### Optimasi untuk Shared Hosting:

1. **Kurangi Ukuran Gambar:**
   - Resize gambar sebelum upload
   - Kompres gambar untuk mengurangi ukuran file

2. **Batasi Jumlah Jurnal:**
   - Download per bulan, bukan per semester/tahun
   - Implementasi pagination jika diperlukan

3. **Monitor Resource Usage:**
   - Gunakan logging untuk monitor memory usage
   - Set timeout yang sesuai dengan shared hosting

### Konfigurasi Shared Hosting yang Direkomendasikan

```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
output_buffering = Off
```

### Testing

1. **Test di Local dengan Konfigurasi Production:**
   ```php
   // Simulasi shared hosting di local
   ini_set('memory_limit', '128M');
   ini_set('max_execution_time', '60');
   ```

2. **Test dengan Data Besar:**
   - Upload beberapa gambar besar
   - Test download jurnal dengan banyak entry

### Monitoring

Setelah deployment, monitor hal berikut:

1. **Log Files:**
   - Error saat generate file
   - Memory usage warnings
   - File corruption issues

2. **Performance:**
   - Waktu generate file
   - Success rate download

3. **User Feedback:**
   - Laporan file corrupt
   - Keluhan download lambat

### Kontak Support

Jika masalah masih berlanjut, hubungi developer dengan informasi:
- Log error dari Laravel
- Konfigurasi PHP hosting
- Contoh file yang corrupt
- Steps to reproduce

---

**Catatan:** Implementasi ini sudah menangani sebagian besar kasus shared hosting. Jika masih ada masalah, kemungkinan perlu konfigurasi khusus dari pihak hosting provider.

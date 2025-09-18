# Journal Calendar Widget - Dokumentasi

## Overview
JournalWidget adalah widget kalender interaktif untuk aplikasi Teaching Journal yang memungkinkan pengguna melakukan operasi CRUD (Create, Read, Update, Delete) pada journal langsung dari tampilan kalender.

## Fitur Utama

### 1. Tampilan Kalender
- Menampilkan journal dalam format kalender bulanan
- Setiap journal ditampilkan sebagai event dengan warna berbeda berdasarkan mata pelajaran
- Tooltip informatif saat hover pada event
- Navigasi bulan dengan tombol prev/next
- View mode: Month, Week, Day

### 2. Operasi CRUD

#### Create (Tambah Journal)
- **Cara 1**: Klik pada tanggal kosong di kalender
- **Cara 2**: Klik tombol "Tambah Journal" di header widget
- Form akan terbuka dengan tanggal yang dipilih sudah terisi
- Field yang tersedia:
  - Tanggal
  - Mata Pelajaran (dengan auto-fill grade)
  - Target Pembelajaran
  - Bab/Materi
  - Kegiatan Pembelajaran (Rich Text Editor)
  - Catatan
  - Foto Kegiatan

#### Read (Lihat Journal)
- Hover pada event untuk melihat tooltip dengan informasi singkat
- Klik pada event untuk membuka detail/edit

#### Update (Edit Journal)
- Klik pada event journal yang sudah ada
- Pilih "OK" pada dialog konfirmasi untuk edit
- Form akan terbuka dengan data journal yang sudah terisi
- Semua field dapat diubah

#### Delete (Hapus Journal)
- Klik pada event journal yang sudah ada
- Pilih "Cancel" pada dialog konfirmasi pertama
- Konfirmasi penghapusan pada dialog kedua
- Journal akan dihapus dan kalender akan refresh otomatis

### 3. Fitur Tambahan
- **Auto Refresh**: Kalender otomatis refresh setelah operasi CRUD
- **Manual Refresh**: Tombol refresh untuk update manual
- **Color Coding**: Setiap mata pelajaran memiliki warna berbeda
- **Responsive**: Tampilan menyesuaikan ukuran layar
- **Localization**: Interface dalam bahasa Indonesia

## Implementasi Teknis

### File Utama
1. `app/Filament/Resources/Journals/Widgets/JournalWidget.php` - Logic utama widget
2. `app/Models/Journal.php` - Model dengan implementasi Eventable interface

### Dependencies
- Guava Calendar Plugin (untuk tampilan kalender)
- Filament Actions (untuk modal CRUD)
- Livewire (untuk komunikasi frontend-backend)

### Integrasi
Widget ini terintegrasi dengan:
- JournalResource (menggunakan form yang sama)
- Journal Model (dengan scope myJournals)
- Subject dan Grade models (untuk relasi)
- Spatie Media Library (untuk upload foto)

## Cara Penggunaan

### 1. Registrasi Widget
Widget sudah terdaftar di `JournalResource::getWidgets()` dan akan muncul di halaman list journals.

### 2. Permissions
Widget menggunakan policy yang sama dengan JournalResource, jadi user hanya bisa melihat dan mengelola journal miliknya sendiri.

### 3. Customization
- Warna event dapat diubah di method `getEventColor()`
- Form dapat dikustomisasi di method `getJournalForm()`
- View kalender dapat dimodifikasi di file blade template

## Event Handlers

### Frontend (Alpine.js)
- `dateClick`: Menangani klik pada tanggal kosong
- `eventClick`: Menangani klik pada event journal
- `refreshCalendar`: Menangani refresh kalender

### Backend (Livewire)
- `handleDateClick($date)`: Membuka form create dengan tanggal terisi
- `handleEventClick($eventId)`: Membuka form edit untuk journal
- `createJournal($date)`: Alternatif method untuk create
- `editJournal($journalId)`: Method untuk edit journal
- `deleteJournal($journalId)`: Method untuk hapus journal
- `refreshEvents()`: Method untuk refresh kalender

## Troubleshooting

### Kalender tidak muncul
- Pastikan FullCalendar.js ter-load dengan benar
- Check console browser untuk error JavaScript

### Event tidak muncul
- Pastikan method `getEventsForCalendar()` mengembalikan data yang benar
- Check apakah user memiliki journal dengan scope `myJournals()`

### Form tidak terbuka
- Pastikan Actions sudah terdaftar di `getActions()`
- Check apakah method `mountAction()` dipanggil dengan benar

### Refresh tidak bekerja
- Pastikan event listener `refreshCalendar` sudah setup
- Check apakah `dispatch('refreshCalendar')` dipanggil setelah CRUD operations

## Future Enhancements
1. Context menu yang lebih sophisticated untuk event actions
2. Drag & drop untuk mengubah tanggal journal
3. Filter berdasarkan mata pelajaran atau kelas
4. Export kalender ke format PDF/iCal
5. Reminder/notification untuk journal yang belum dibuat

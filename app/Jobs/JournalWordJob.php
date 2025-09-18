<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Journal;
use App\Notifications\JournalFileFinished;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class JournalWordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $journals;
    public $userId;
    /**
     * Create a new job instance.
     */
    public function __construct($journals, $userId = null)
    {
        $this->journals = $journals;
        $this->userId = $userId ?? ($journals['user_id'] ?? null);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('JournalWordJob started', [
            'journals_data' => $this->journals,
            'user_id' => $this->userId
        ]);

        // 1. Ambil data dari model Journal
        $journals = Journal::query()
            ->where('user_id', $this->journals['user_id'])
            ->where('academic_year_id', $this->journals['academic_year_id'])
            ->where('grade_id', $this->journals['grade_id'])
            ->where('subject_id', $this->journals['subject_id'])
            ->whereMonth('date', $this->journals['month'])
            ->orderBy('date', 'asc')
            ->get();

        // Check if journals exist
        if ($journals->isEmpty()) {
            Log::error('No journals found for criteria', $this->journals);
            
            // Kirim notifikasi ke user bahwa tidak ada journal ditemukan
            if ($this->userId) {
                $recipient = \App\Models\User::find($this->userId);
                
                if ($recipient) {
                    $monthName = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    
                    $month = $monthName[$this->journals['month']] ?? 'Bulan tidak diketahui';
                    
                    // Kirim notifikasi Filament
                    Notification::make()
                        ->title('Tidak Ada Jurnal Ditemukan')
                        ->body("Tidak ada jurnal yang ditemukan untuk bulan {$month}. Pastikan Anda sudah membuat jurnal untuk periode tersebut.")
                        ->icon('heroicon-o-exclamation-triangle')
                        ->warning()
                        ->sendToDatabase($recipient);
                    
                    Log::info('Notifikasi "tidak ada jurnal" berhasil dikirim ke user ID: ' . $this->userId);
                } else {
                    Log::error('User tidak ditemukan dengan ID: ' . $this->userId);
                }
            } else {
                Log::error('User ID tidak tersedia untuk mengirim notifikasi');
            }
            
            return;
        }

        Log::info('Found journals', ['count' => $journals->count()]);

        // 2. Generate Word
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText(
            'Laporan Jurnal Mengajar',
            [
                'alignment' => 'center',
                'size' => 24,
                'bold' => true,
            ]
        );

        $firstJournal = $journals->first();
        $section->addText(
            'Mata Pelajaran: ' . ($firstJournal->subject->name ?? 'N/A'),
            [
                'bold' => true,
                'size' => 14,
            ]
        );
        $section->addText(
            'Tahun Ajaran: ' . ($firstJournal->academicYear->year ?? 'N/A'),
            [
                'bold' => true,
                'size' => 14,
            ]
        );
        $section->addText(
            'Semester: ' . ($firstJournal->academicYear->semester?->getLabel() ?? 'N/A') ,
            [
                'bold' => true,
                'size' => 14,
            ]
        );
        $section->addText(
            'Periode: ' . $journals->last()->date->format('d F Y') . ' - ' . $journals->first()->date->format('d F Y'),
            [
                'bold' => true,
                'size' => 14,
            ]
        );

        foreach ($journals as $journal) {
            $section->addText('--------------------------------****--------------------------------');
            $section->addText(
                $journal->date->format('d F Y'),
                [
                    'alignment' => 'center',
                    'size' => 14,
                ]
            );
            $section->addText('Target:', ['bold' => true]);
            $section->addText($journal->target);
            $section->addText('Chapter:', ['bold' => true]);
            $section->addText($journal->chapter);
            $section->addText('Aktivitas:', ['bold' => true]);
            // $section->addText($journal->activity);
            Html::addHtml($section, $journal->activity);
            $section->addText('Catatan:', ['bold' => true]);
            $section->addText($journal->notes);

            // attendance
            $section->addText('Ketidakhadiran:', ['bold' => true]);
            $attendance = $journal->attendance;
            foreach ($attendance as $item) {
                $section->addListItem($item->student->name . ' - ' . $item->status->getLabel());
            }

            $section->addText('Dokumentasi Kegiatan:', ['bold' => true]);

            $images = $journal->getMedia('activity_photos');
            
            // if image is empty, add text 'Jurnal ini tidak memiliki dokumentasi kegiatan'
            if($images->isEmpty()) {
                $section->addText('Jurnal ini tidak memiliki dokumentasi kegiatan');
            } else {
                foreach ($images as $image) {
                    try {
                        // Dapatkan path file yang sebenarnya dari media library
                        $imagePath = $image->getPath();

                        // Pastikan file gambar ada dan dapat dibaca
                        if (file_exists($imagePath) && is_readable($imagePath)) {
                            // Validasi bahwa ini adalah file gambar
                            $imageInfo = getimagesize($imagePath);
                            if ($imageInfo !== false) {
                                $section->addImage($imagePath, [
                                    'width'         => 200,
                                    'wrappingStyle' => 'inline'
                                ]);
                                $section->addTextBreak(1); // Tambahkan spasi antar gambar
                            } else {
                                Log::warning("File bukan gambar yang valid: " . $imagePath);
                            }
                        } else {
                            Log::warning("File gambar tidak ditemukan atau tidak dapat dibaca: " . $imagePath);
                        }
                    } catch (\Exception $e) {
                        Log::error("Error saat memproses gambar: " . $e->getMessage());
                        // Lanjutkan ke gambar berikutnya tanpa menghentikan proses
                        continue;
                    }
                }
            }

            // add signature from teacher
            $section->addText('Mengetahui,');
            $section->addText('Guru Pengajar');
            $section->addTextBreak(5);
            $section->addText('Nama: ' . $journal->user->name, ['bold' => true]);
            $section->addText('NIP: ' . ( $journal->user->nip ?? '-'), ['bold' => true]);

            // add signature from headmaster
            $section->addTextBreak(1);
            $section->addText('Mengetahui,');
            $section->addText('Kepala Sekolah');
            $section->addTextBreak(5);
            $section->addText('Nama: ' . $journal->academicYear->headmaster_name, ['bold' => true]);
            $section->addText('NIP: ' . ( $journal->academicYear->headmaster_nip ?? '-'), ['bold' => true]);

            // add note
            $section->addTextBreak(2);
            $section->addText('Catatan Kepala Sekolah: ' );
            $section->addText('...............................................');

            // add page break
            $section->addPageBreak();
        }

        // Add Page Break
        $section->addPageBreak();

        $phpWord->addNumberingStyle(
            'multilevel',
            array(
                'type' => 'multilevel',
                'levels' => array(
                    array('format' => 'decimal', 'text' => '%1.', 'left' => 360, 'hanging' => 360, 'tabPos' => 360),
                    array('format' => 'lowerLetter', 'text' => '%2.', 'left' => 720, 'hanging' => 360, 'tabPos' => 720),
                    array('format' => 'bullet', 'text' => '•', 'left' => 1080, 'hanging' => 360, 'tabPos' => 1080),
                )
            )
        );

        // rekap attendance
        $section->addText('Rekap Ketidakhadiran:', ['bold' => true, 'size' => 14]);
        $attendance = Attendance::query()
            ->whereIn('journal_id', $journals->pluck('id'))
            ->get();
        
        $attendanceByStudent = $attendance->groupBy('student_id');
        $studentNumber = 1;
        
        $attendanceByStudent->each(function ($studentAttendance) use ($section, &$studentNumber) {
            $studentName = $studentAttendance->first()->student->name;
            
            // Tambahkan nama siswa dengan nomor urut
            $section->addListItem($studentName, 0, ['bold' => true, 'numbering' => 'multilevel']);
            
            // Group attendance by status untuk siswa ini
            $attendanceByStatus = $studentAttendance->groupBy('status');
            
            // Tampilkan setiap status dengan jumlahnya dan tanggalnya
            foreach (\App\StatusAttendanceEnum::cases() as $status) {
                $statusAttendance = $attendanceByStatus->get($status->value);
                $count = $statusAttendance?->count() ?? 0;
                
                if ($count > 0) {
                    $section->addListItem($status->getLabel() . ': ' . $count . ' kali', 1, ['numbering' => 'multilevel']);
                    
                    // Tampilkan tanggal-tanggal untuk status ini
                    $statusAttendance->each(function ($attendance) use ($section) {
                        $date = $attendance->date->format('d/m/Y');
                        $section->addListItem($date, 2, ['numbering' => 'multilevel']);
                    });
                }
            }
            
            $studentNumber++;
        });

        // add signature from teacher
        $section->addText('Mengetahui,');
        $section->addText('Guru Pengajar');
        $section->addTextBreak(5);
        $section->addText('Nama: ' . $journal->user->name, ['bold' => true]);
        $section->addText('NIP: ' . ( $journal->user->nip ?? '-'), ['bold' => true]);

        // add signature from headmaster
        $section->addTextBreak(1);
        $section->addText('Mengetahui,');
        $section->addText('Kepala Sekolah');
        $section->addTextBreak(5);
        $section->addText('Nama: ' . $journal->academicYear->headmaster_name, ['bold' => true]);
        $section->addText('NIP: ' . ( $journal->academicYear->headmaster_nip ?? '-'), ['bold' => true]);

        try {
            // Pastikan direktori journals ada
            $journalsDir = storage_path('app/public/journals');
            if (!file_exists($journalsDir)) {
                mkdir($journalsDir, 0755, true);
            }

            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $filename = 'jurnal_' . date('Y-m-d') . '_' . \Ramsey\Uuid\Uuid::uuid4()->toString() . '.docx';
            $fullpath = $journalsDir . '/' . $filename;

            $writer->save($fullpath);

            Log::info("Word document berhasil dibuat: " . $fullpath);

            // Simpan path file untuk referensi jika diperlukan
            $this->journals['generated_file'] = $fullpath;
        } catch (\Throwable $th) {
            Log::error("Error saat membuat Word document: " . $th->getMessage());
            Log::error("Stack trace: " . $th->getTraceAsString());
            
            // Kirim notifikasi ke user bahwa terjadi error
            if ($this->userId) {
                $recipient = \App\Models\User::find($this->userId);
                
                if ($recipient) {
                    Notification::make()
                        ->title('Gagal Membuat File Jurnal')
                        ->body('Terjadi kesalahan saat membuat file Word jurnal. Silakan coba lagi atau hubungi administrator.')
                        ->icon('heroicon-o-x-circle')
                        ->danger()
                        ->sendToDatabase($recipient);
                    
                    Log::info('Notifikasi error berhasil dikirim ke user ID: ' . $this->userId);
                }
            }
            
            throw $th; // Re-throw untuk memastikan job gagal jika ada error
        }


        // 3. Kirim notifikasi ke user
        Log::info('Attempting to send notifications', ['user_id' => $this->userId]);

        if ($this->userId) {
            $recipient = \App\Models\User::find($this->userId);

            if ($recipient) {
                Log::info('User found, sending notifications', [
                    'user_id' => $recipient->id,
                    'user_name' => $recipient->name,
                    'file_path' => $fullpath
                ]);

                try {
                    // Kirim notifikasi custom
                    $recipient->notify(new JournalFileFinished($this->journals, $fullpath));
                    Log::info('Custom notification sent successfully');

                    // Kirim notifikasi Filament
                    Notification::make()
                        ->title('File Jurnal Siap')
                        ->body('File jurnal Word Anda sudah siap untuk didownload')
                        ->icon('heroicon-o-document-text')
                        ->success()
                        ->actions([
                            Action::make('download')
                                ->label('Download')
                                ->button()
                                ->url(asset('storage/journals/' . basename($fullpath)))
                                ->openUrlInNewTab(),
                            Action::make('read')
                                ->label('Mark as Read')
                                ->markAsRead(),
                        ])
                        ->send()
                        ->sendToDatabase($recipient);

                    Log::info('Filament notification sent successfully');
                    Log::info('All notifications sent successfully to user ID: ' . $this->userId);
                } catch (\Exception $e) {
                    Log::error('Error sending notifications: ' . $e->getMessage());
                    Log::error('Stack trace: ' . $e->getTraceAsString());
                }
            } else {
                Log::error('User tidak ditemukan dengan ID: ' . $this->userId);
            }
        } else {
            Log::error('User ID tidak tersedia untuk mengirim notifikasi');
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Target;
use App\Models\User;
use App\Notifications\JournalFileFinished;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class JournalWordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $journals;
    public ?string $userId;

    public function __construct(array $journals, ?string $userId = null)
    {
        $this->journals = $journals;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        Log::info('JournalWordJob started', [
            'journals_data' => $this->journals,
            'user_id' => $this->userId
        ]);

        $journals = Journal::query()
            ->where('user_id', $this->journals['user_id'])
            ->where('academic_year_id', $this->journals['academic_year_id'])
            ->where('grade_id', $this->journals['grade_id'])
            ->where('subject_id', $this->journals['subject_id'])
            ->whereMonth('date', $this->journals['month'])
            ->with(['subject', 'academicYear', 'user', 'targets', 'attendance.student', 'media'])
            ->orderBy('date', 'asc')
            ->get();

        if ($journals->isEmpty()) {
            $this->sendNoJournalNotification();
            return;
        }

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $firstJournal = $journals->first();
        $lastJournal = $journals->last();

        $this->addHeaderToSection($section, $firstJournal, $lastJournal);

        foreach ($journals as $journal) {
            $this->addJournalContentToSection($section, $journal);
        }

        $this->addAttendanceSummaryToSection($section, $journals);

        $this->addSignaturesToSection($section, $firstJournal);

        try {
            $this->saveWordDocument($phpWord, $firstJournal->user->id);
            Log::info("Word document successfully created.");
            $this->sendSuccessNotification();
        } catch (\Throwable $th) {
            Log::error("Error creating Word document: " . $th->getMessage());
            $this->sendErrorNotification();
            throw $th;
        }
    }

    protected function addHeaderToSection($section, $firstJournal, $lastJournal)
    {
        $section->addText('Laporan Jurnal Mengajar', ['alignment' => 'center', 'size' => 24, 'bold' => true]);
        $section->addText('Mata Pelajaran: ' . ($firstJournal->subject->name ?? 'N/A'), ['bold' => true, 'size' => 14]);
        $section->addText('Tahun Ajaran: ' . ($firstJournal->academicYear->year ?? 'N/A'), ['bold' => true, 'size' => 14]);
        $section->addText('Semester: ' . ($firstJournal->academicYear->semester?->getLabel() ?? 'N/A'), ['bold' => true, 'size' => 14]);
        $section->addText('Periode: ' . $lastJournal->date->format('d F Y') . ' - ' . $firstJournal->date->format('d F Y'), ['bold' => true, 'size' => 14]);
    }

    protected function addJournalContentToSection($section, $journal)
    {
        $section->addTextBreak(1);
        $section->addText('--------------------------------****--------------------------------');
        $section->addText($journal->date->format('d F Y'), ['alignment' => 'center', 'size' => 14]);
        $section->addText('Main Target:', ['bold' => true]);
        $section->addText($journal->mainTarget->main_target);
        $section->addText('Target:', ['bold' => true]);
        
        $journal->targets->each(function ($target) use ($section) {
            $section->addListItem($target->target);
        });

        $section->addText('Chapter:', ['bold' => true]);
        $section->addText($journal->chapter);
        $section->addText('Aktivitas:', ['bold' => true]);
        Html::addHtml($section, $journal->activity);
        $section->addText('Catatan:', ['bold' => true]);
        $section->addText($journal->notes);

        $this->addAttendanceToList($section, $journal);
        $this->addActivityPhotos($section, $journal);
        
        $section->addPageBreak();
    }

    protected function addAttendanceToList($section, $journal)
    {
        $section->addText('Ketidakhadiran:', ['bold' => true]);
        $journal->attendance->each(function ($item) use ($section) {
            $section->addListItem($item->student->name . ' - ' . $item->status->getLabel());
        });
    }

    protected function addActivityPhotos($section, $journal)
    {
        $section->addText('Dokumentasi Kegiatan:', ['bold' => true]);
        $images = $journal->getMedia('activity_photos');

        if ($images->isEmpty()) {
            $section->addText('Jurnal ini tidak memiliki dokumentasi kegiatan');
        } else {
            foreach ($images as $image) {
                try {
                    $imagePath = $image->getPath();
                    if (file_exists($imagePath) && is_readable($imagePath) && getimagesize($imagePath) !== false) {
                        $section->addImage($imagePath, [
                            'width' => 200,
                            'wrappingStyle' => 'inline'
                        ]);
                        $section->addTextBreak(1);
                    } else {
                        Log::warning("File gambar tidak valid, tidak ditemukan, atau tidak dapat dibaca: " . $imagePath);
                    }
                } catch (\Exception $e) {
                    Log::error("Error saat memproses gambar: " . $e->getMessage());
                    continue;
                }
            }
        }
    }

    protected function addAttendanceSummaryToSection($section, $journals)
    {
        $section->addTextBreak(1);
        $section->addText('Rekap Ketidakhadiran:', ['bold' => true, 'size' => 14]);

        $attendance = Attendance::query()
            ->whereIn('journal_id', $journals->pluck('id'))
            ->get();

        $attendanceByStudent = $attendance->groupBy('student_id');

        if ($attendanceByStudent->isEmpty()) {
            $section->addText('Semua siswa hadir.');
        } else {
            $attendanceByStudent->each(function ($studentAttendance) use ($section) {
                $studentName = $studentAttendance->first()->student->name;
                $section->addListItem($studentName, 0, ['bold' => true]);
                
                $attendanceByStatus = $studentAttendance->groupBy('status');
                foreach (\App\StatusAttendanceEnum::cases() as $status) {
                    $statusAttendance = $attendanceByStatus->get($status->value);
                    $count = $statusAttendance?->count() ?? 0;
                    if ($count > 0) {
                        $section->addListItem($status->getLabel() . ': ' . $count . ' kali', 1);
                        $statusAttendance->each(function ($attendance) use ($section) {
                            $date = $attendance->date->format('d/m/Y');
                            $section->addListItem($date, 2);
                        });
                    }
                }
            });
        }
    }

    protected function addSignaturesToSection($section, $journal)
    {
        $section->addTextBreak(2);
        $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80]);
        $table->addRow();
        
        $cell1 = $table->addCell(4500);
        $cell1->addText('Mengetahui,', ['alignment' => 'center']);
        $cell1->addText('Kepala Sekolah', ['alignment' => 'center']);
        $cell1->addTextBreak(4);
        $cell1->addText('Nama: ' . $journal->academicYear->headmaster_name, ['bold' => true, 'alignment' => 'center']);
        $cell1->addText('NIP: ' . ($journal->academicYear->headmaster_nip ?? '-'), ['bold' => true, 'alignment' => 'center']);
        
        $cell2 = $table->addCell(4500);
        $cell2->addText('Mengetahui,', ['alignment' => 'center']);
        $cell2->addText('Guru Pengajar', ['alignment' => 'center']);
        $cell2->addTextBreak(4);
        $cell2->addText('Nama: ' . $journal->user->name, ['bold' => true, 'alignment' => 'center']);
        $cell2->addText('NIP: ' . ($journal->user->nip ?? '-'), ['bold' => true, 'alignment' => 'center']);
    }

    protected function saveWordDocument(PhpWord $phpWord, $userId)
    {
        $journalsDir = storage_path('app/public/journals');
        if (!file_exists($journalsDir)) {
            mkdir($journalsDir, 0755, true);
        }

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $filename = 'jurnal_' . date('Y-m-d') . '_' . \Ramsey\Uuid\Uuid::uuid4()->toString() . '.docx';
        $fullpath = $journalsDir . '/' . $filename;

        $writer->save($fullpath);
    }

    protected function sendNoJournalNotification()
    {
        if ($this->userId) {
            $recipient = User::find($this->userId);
            if ($recipient) {
                $monthName = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $month = $monthName[$this->journals['month']] ?? 'Bulan tidak diketahui';

                Notification::make()
                    ->title('Tidak Ada Jurnal Ditemukan')
                    ->body("Tidak ada jurnal yang ditemukan untuk bulan {$month}. Pastikan Anda sudah membuat jurnal untuk periode tersebut.")
                    ->icon('heroicon-o-exclamation-triangle')
                    ->warning()
                    ->sendToDatabase($recipient);
            }
        }
    }

    protected function sendSuccessNotification()
    {
        if ($this->userId) {
            $recipient = User::find($this->userId);
            if ($recipient) {
                Notification::make()
                    ->title('File Jurnal Siap')
                    ->body('File jurnal Word Anda sudah siap untuk diunduh.')
                    ->icon('heroicon-o-document-text')
                    ->success()
                    ->actions([
                        Action::make('download')
                            ->label('Download')
                            ->button()
                            ->url(asset('storage/journals/' . basename($this->journals['generated_file'])))
                            ->openUrlInNewTab(),
                    ])
                    ->sendToDatabase($recipient);
            }
        }
    }

    protected function sendErrorNotification()
    {
        if ($this->userId) {
            $recipient = User::find($this->userId);
            if ($recipient) {
                Notification::make()
                    ->title('Gagal Membuat File Jurnal')
                    ->body('Terjadi kesalahan saat membuat file Word jurnal. Silakan coba lagi atau hubungi administrator.')
                    ->icon('heroicon-o-x-circle')
                    ->danger()
                    ->sendToDatabase($recipient);
            }
        }
    }
}
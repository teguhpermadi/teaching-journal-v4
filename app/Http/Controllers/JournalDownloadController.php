<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JournalDownloadController extends Controller
{
    public function downloadJournal(Request $request)
    {
        try {
            Log::info('Journal download started', [
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            // Validasi input
            $request->validate([
                'user_id' => 'required',
                'academic_year_id' => 'required',
                'grade_id' => 'required',
                'subject_id' => 'required',
                'month' => 'required|integer|min:1|max:12'
            ]);

            // 1. Ambil data dari model Journal
            $journals = Journal::query()
                ->where('user_id', $request->user_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('grade_id', $request->grade_id)
                ->where('subject_id', $request->subject_id)
                ->whereMonth('date', $request->month)
                ->orderBy('date', 'asc')
                ->get();

            // Check if journals exist
            if ($journals->isEmpty()) {
                Log::warning('No journals found for criteria', $request->all());
                return response()->json([
                    'error' => 'Tidak ada jurnal ditemukan untuk kriteria yang diberikan'
                ], 404);
            }

            Log::info('Found journals', ['count' => $journals->count()]);

            // 2. Generate Word Document
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            
            // Header
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
                'Semester: ' . ($firstJournal->academicYear->semester?->getLabel() ?? 'N/A'),
                [
                    'bold' => true,
                    'size' => 14,
                ]
            );
            $section->addText(
                'Periode: ' . $journals->first()->date->format('d F Y') . ' - ' . $journals->last()->date->format('d F Y'),
                [
                    'bold' => true,
                    'size' => 14,
                ]
            );

            // Journal entries
            foreach ($journals as $journal) {
                $section->addText('--------------------------------****--------------------------------');
                $section->addText(
                    $journal->date->format('d F Y'),
                    [
                        'alignment' => 'center',
                        'size' => 14,
                    ]
                );
                $section->addText('Main Target:', ['bold' => true]);
                $section->addText($journal->mainTarget->main_target);
                $section->addText('Target:', ['bold' => true]);
                
                // Add list target
                foreach ($journal->target_id as $target_id) {
                    $target = Target::find($target_id);
                    if ($target) {
                        $section->addListItem($target->target);
                    }
                }

                $section->addText('Chapter:', ['bold' => true]);
                $section->addText($journal->chapter);
                $section->addText('Aktivitas:', ['bold' => true]);
                
                try {
                    Html::addHtml($section, $journal->activity);
                } catch (\Exception $e) {
                    Log::warning('Error adding HTML activity, using plain text', [
                        'journal_id' => $journal->id,
                        'error' => $e->getMessage()
                    ]);
                    $section->addText(strip_tags($journal->activity));
                }
                
                $section->addText('Catatan:', ['bold' => true]);
                $section->addText($journal->notes);

                // Attendance
                $section->addText('Ketidakhadiran:', ['bold' => true]);
                $attendance = $journal->attendance;
                foreach ($attendance as $item) {
                    $section->addListItem($item->student->name . ' - ' . $item->status->getLabel());
                }

                $section->addText('Dokumentasi Kegiatan:', ['bold' => true]);

                $images = $journal->getMedia('activity_photos');

                if ($images->isEmpty()) {
                    $section->addText('Jurnal ini tidak memiliki dokumentasi kegiatan');
                } else {
                    foreach ($images as $image) {
                        try {
                            $imagePath = $image->getPath();

                            // Log untuk debugging
                            Log::info('Processing image', [
                                'image_id' => $image->id,
                                'path' => $imagePath,
                                'exists' => file_exists($imagePath),
                                'readable' => is_readable($imagePath)
                            ]);

                            if (file_exists($imagePath) && is_readable($imagePath)) {
                                $imageInfo = getimagesize($imagePath);
                                if ($imageInfo !== false) {
                                    $section->addImage($imagePath, [
                                        'width' => 200,
                                        'wrappingStyle' => 'inline'
                                    ]);
                                    $section->addTextBreak(1);
                                } else {
                                    Log::error("File corrupt - bukan gambar yang valid", [
                                        'file_path' => $imagePath,
                                        'journal_id' => $journal->id,
                                        'image_id' => $image->id
                                    ]);
                                    $section->addText('[Gambar corrupt: ' . basename($imagePath) . ']');
                                }
                            } else {
                                Log::error("File corrupt - tidak ditemukan atau tidak dapat dibaca", [
                                    'file_path' => $imagePath,
                                    'journal_id' => $journal->id,
                                    'image_id' => $image->id,
                                    'file_exists' => file_exists($imagePath),
                                    'is_readable' => is_readable($imagePath)
                                ]);
                                $section->addText('[File tidak ditemukan: ' . basename($imagePath) . ']');
                            }
                        } catch (\Exception $e) {
                            Log::error("File corrupt - error saat memproses gambar", [
                                'journal_id' => $journal->id,
                                'image_id' => $image->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            $section->addText('[Error memproses gambar: ' . $e->getMessage() . ']');
                            continue;
                        }
                    }
                }

                // Signature table
                // $this->addSignatureTable($section, $journal);

                // Add page break except for last journal
                if (!$journal->is($journals->last())) {
                    $section->addPageBreak();
                }
            }

            // Add attendance summary
            $this->addAttendanceSummary($section, $journals);

            // Final signature table
            // $this->addSignatureTable($section, $firstJournal);

            // Generate filename
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            
            $monthName = $monthNames[$request->month];
            $subjectName = str_replace(' ', '_', $firstJournal->subject->name ?? 'Subject');
            $filename = "Jurnal_{$subjectName}_{$monthName}_{$firstJournal->academicYear->year}.docx";

            // Create streamed response for direct download
            return new StreamedResponse(function() use ($phpWord) {
                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]);

        } catch (\Exception $e) {
            Log::error("Error generating journal download", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat file jurnal: ' . $e->getMessage()
            ], 500);
        }
    }

    private function addSignatureTable($section, $journal)
    {
        $tableStyle = array(
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 80
        );

        $cellStyle = array(
            'borderSize' => 0,
            'borderColor' => 'FFFFFF'
        );

        $table = $section->addTable($tableStyle);
        $table->addRow();

        // Kolom pertama - Tanda tangan Kepala Sekolah
        $cell1 = $table->addCell(4500, $cellStyle);
        $cell1->addText('Mengetahui,', ['alignment' => 'center']);
        $cell1->addText('Kepala Sekolah', ['alignment' => 'center']);
        $cell1->addTextBreak(4);
        $cell1->addText('Nama: ' . $journal->academicYear->headmaster_name, ['bold' => true, 'alignment' => 'center']);
        $cell1->addText('NIP: ' . ($journal->academicYear->headmaster_nip ?? '-'), ['bold' => true, 'alignment' => 'center']);

        // Kolom kedua - Tanda tangan Guru
        $cell2 = $table->addCell(4500, $cellStyle);
        $cell2->addText('Mengetahui,', ['alignment' => 'center']);
        $cell2->addText('Guru Pengajar', ['alignment' => 'center']);
        $cell2->addTextBreak(4);
        $cell2->addText('Nama: ' . $journal->user->name, ['bold' => true, 'alignment' => 'center']);
        $cell2->addText('NIP: ' . ($journal->user->nip ?? '-'), ['bold' => true, 'alignment' => 'center']);

        $section->addTextBreak(2);
        $section->addText('Catatan Kepala Sekolah: ');
        $section->addText('...............................................');
    }

    private function addAttendanceSummary($section, $journals)
    {
        $section->addPageBreak();

        $phpWord = $section->getPhpWord();
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

        $section->addText('Rekap Ketidakhadiran:', ['bold' => true, 'size' => 14]);
        $attendance = Attendance::query()
            ->whereIn('journal_id', $journals->pluck('id'))
            ->get();

        $attendanceByStudent = $attendance->groupBy('student_id');

        if ($attendanceByStudent->isEmpty()) {
            $section->addText('Pada bulan ' . $journals->first()->date->format('F Y') . ', semua siswa hadir');
        } else {
            $section->addText('Pada bulan ' . $journals->first()->date->format('F Y') . ', rekap ketidakhadiran:');
            $attendanceByStudent->each(function ($studentAttendance) use ($section) {
                $studentName = $studentAttendance->first()->student->name;
                $section->addListItem($studentName, 0, ['bold' => true, 'numbering' => 'multilevel']);

                $attendanceByStatus = $studentAttendance->groupBy('status');

                foreach (\App\StatusAttendanceEnum::cases() as $status) {
                    $statusAttendance = $attendanceByStatus->get($status->value);
                    $count = $statusAttendance?->count() ?? 0;

                    if ($count > 0) {
                        $section->addListItem($status->getLabel() . ': ' . $count . ' kali', 1, ['numbering' => 'multilevel']);

                        $statusAttendance->each(function ($attendance) use ($section) {
                            $date = $attendance->date->format('d/m/Y');
                            $section->addListItem($date, 2, ['numbering' => 'multilevel']);
                        });
                    }
                }
            });
        }
    }
}

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
            // Clear any existing output buffers to prevent corruption
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            Log::info('Journal download started', [
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
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

                            // Log untuk debugging dengan informasi lebih lengkap
                            Log::info('Processing image', [
                                'image_id' => $image->id,
                                'path' => $imagePath,
                                'exists' => file_exists($imagePath),
                                'readable' => is_readable($imagePath),
                                'filesize' => file_exists($imagePath) ? filesize($imagePath) : 0,
                                'mime_type' => file_exists($imagePath) ? mime_content_type($imagePath) : null
                            ]);

                            if (file_exists($imagePath) && is_readable($imagePath)) {
                                // Check file size to prevent memory issues
                                $fileSize = filesize($imagePath);
                                if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                                    Log::warning("Image file too large, skipping", [
                                        'file_path' => $imagePath,
                                        'file_size' => $fileSize,
                                        'journal_id' => $journal->id
                                    ]);
                                    $section->addText('[Gambar terlalu besar: ' . basename($imagePath) . ' (' . round($fileSize/1024/1024, 2) . 'MB)]');
                                    continue;
                                }

                                $imageInfo = getimagesize($imagePath);
                                if ($imageInfo !== false) {
                                    // Validate image dimensions to prevent memory issues
                                    $width = $imageInfo[0];
                                    $height = $imageInfo[1];
                                    
                                    if ($width > 4000 || $height > 4000) {
                                        Log::warning("Image dimensions too large, skipping", [
                                            'file_path' => $imagePath,
                                            'width' => $width,
                                            'height' => $height,
                                            'journal_id' => $journal->id
                                        ]);
                                        $section->addText('[Gambar terlalu besar: ' . basename($imagePath) . ' (' . $width . 'x' . $height . ')]');
                                        continue;
                                    }

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
                $this->addSignatureTable($section, $journal);

                // Add page break except for last journal
                if (!$journal->is($journals->last())) {
                    $section->addPageBreak();
                }
            }

            // Add attendance summary
            $this->addAttendanceSummary($section, $journals);

            // Final signature table
            $this->addSignatureTable($section, $firstJournal);

            // Generate filename
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            
            $monthName = $monthNames[$request->month];
            
            // Clean subject name and academic year for filename
            $subjectName = $this->cleanFilename($firstJournal->subject->name ?? 'Subject');
            $subjectName = str_replace(' ', '_', $subjectName);
            
            $academicYear = $this->cleanFilename($firstJournal->academicYear->year ?? date('Y'));
            
            $filename = "Jurnal_{$subjectName}_{$monthName}_{$academicYear}.docx";
            
            // Log filename generation for debugging
            Log::info('Generated filename', [
                'original_subject' => $firstJournal->subject->name ?? 'Subject',
                'cleaned_subject' => $subjectName,
                'original_year' => $firstJournal->academicYear->year ?? date('Y'),
                'cleaned_year' => $academicYear,
                'final_filename' => $filename
            ]);

            // Check if we should use alternative method for shared hosting
            $useAlternativeMethod = config('app.use_alternative_download', false);
            
            if ($useAlternativeMethod) {
                return $this->downloadJournalAlternative($phpWord, $filename);
            }

            // Create streamed response for direct download with better error handling
            return new StreamedResponse(function() use ($phpWord, $filename) {
                try {
                    // Ensure no output before file content
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Set error reporting to prevent any notices/warnings from corrupting output
                    $originalErrorReporting = error_reporting(0);
                    
                    // Create writer and save to output
                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                    $writer->save('php://output');
                    
                    // Restore error reporting
                    error_reporting($originalErrorReporting);
                    
                    Log::info('Journal file successfully generated', ['filename' => $filename]);
                    
                } catch (\Exception $e) {
                    Log::error('Error in StreamedResponse', [
                        'error' => $e->getMessage(),
                        'filename' => $filename
                    ]);
                    // Don't output anything else as it will corrupt the file
                    throw $e;
                }
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
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

    /**
     * Clean filename by removing invalid characters
     */
    private function cleanFilename($filename)
    {
        // Remove invalid filename characters: / \ : * ? " < > |
        $cleaned = preg_replace('/[\/\\\\:*?"<>|]/', '_', $filename);
        
        // Replace multiple underscores with single underscore
        $cleaned = preg_replace('/_+/', '_', $cleaned);
        
        // Trim underscores from start and end
        $cleaned = trim($cleaned, '_');
        
        return $cleaned;
    }

    /**
     * Alternative download method for shared hosting environments
     * This method saves the file temporarily and then serves it
     */
    private function downloadJournalAlternative($phpWord, $filename)
    {
        try {
            // Ensure filename is clean for temporary path
            $cleanFilename = $this->cleanFilename($filename);
            
            // Create temporary file path
            $tempPath = storage_path('app/temp/' . uniqid() . '_' . $cleanFilename);
            
            // Ensure temp directory exists
            $tempDir = dirname($tempPath);
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Save to temporary file first
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempPath);
            
            Log::info('Journal file saved to temp', [
                'temp_path' => $tempPath,
                'file_size' => filesize($tempPath)
            ]);
            
            // Return file download response
            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Error in alternative download method', [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            
            // Clean up temp file if it exists
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            throw $e;
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

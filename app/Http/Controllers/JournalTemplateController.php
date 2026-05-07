<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Journal;
use App\Models\MainTarget;
use App\Models\Subject;
use App\Models\Target;
use App\TeachingStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;

class JournalTemplateController extends Controller
{
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'subject_id' => 'required',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $subject = Subject::findOrFail($request->subject_id);

        // Validasi subject milik user yang login
        if ($subject->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $grade = Grade::findOrFail($subject->grade_id);
        $academicYear = AcademicYear::active()->firstOrFail();
        $month = (int) $request->month;
        $year = (int) $academicYear->year;

        // Hitung jumlah hari dalam bulan
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Mapping nama hari ke Bahasa Indonesia
        $dayNames = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        // Generate Word Document - Landscape
        $phpWord = new PhpWord;
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1500,
            'marginRight' => 1500,
        ]);

        // Header Info
        $section->addText(
            'Template Jurnal Mengajar',
            [
                'alignment' => 'center',
                'size' => 20,
                'bold' => true,
            ]
        );

        $section->addTextBreak(1);

        // Subject ID
        $section->addText('Subject ID: '.$subject->id, ['size' => 11]);

        // Grade ID
        $section->addText('Grade ID: '.$grade->id, ['size' => 11]);

        // Month
        $section->addText('Month: '.$month, ['size' => 11]);

        $section->addTextBreak(1);

        // Main Target - tabel dengan nomor urut dan deskripsi
        $mainTargets = MainTarget::where('subject_id', $subject->id)->orderBy('id')->get();
        $section->addText('Daftar Main Target:', ['bold' => true, 'size' => 11]);

        $mainTargetTableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ];
        $phpWord->addTableStyle('MainTargetTable', $mainTargetTableStyle);
        $mainTargetTable = $section->addTable('MainTargetTable');

        // Header row untuk Main Target
        $mtHeaderRow = $mainTargetTable->addRow(400);
        $mtHeaderRow->addCell(1000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('No', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $mtHeaderRow->addCell(8000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Deskripsi', ['bold' => true, 'size' => 10], ['alignment' => 'center']);

        $mainTargetPositionMap = [];
        foreach ($mainTargets as $index => $mt) {
            $position = $index + 1;
            $mainTargetPositionMap[$position] = $mt->id;
            $mtRow = $mainTargetTable->addRow(300);
            $mtRow->addCell(1000, ['vAlign' => 'center'])->addText((string) $position, ['size' => 10], ['alignment' => 'center']);
            $mtRow->addCell(8000, ['vAlign' => 'center'])->addText($mt->main_target, ['size' => 10]);
        }

        $section->addTextBreak(1);

        // Target - tabel dengan nomor urut dan deskripsi
        $targets = Target::where('subject_id', $subject->id)->orderBy('id')->get();
        $section->addText('Daftar Target:', ['bold' => true, 'size' => 11]);

        $targetTableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ];
        $phpWord->addTableStyle('TargetTable', $targetTableStyle);
        $targetTable = $section->addTable('TargetTable');

        // Header row untuk Target
        $tHeaderRow = $targetTable->addRow(400);
        $tHeaderRow->addCell(1000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('No', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $tHeaderRow->addCell(8000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Deskripsi', ['bold' => true, 'size' => 10], ['alignment' => 'center']);

        $targetPositionMap = [];
        foreach ($targets as $index => $t) {
            $position = $index + 1;
            $targetPositionMap[$position] = $t->id;
            $tRow = $targetTable->addRow(300);
            $tRow->addCell(1000, ['vAlign' => 'center'])->addText((string) $position, ['size' => 10], ['alignment' => 'center']);
            $tRow->addCell(8000, ['vAlign' => 'center'])->addText($t->target, ['size' => 10]);
        }

        $section->addTextBreak(1);

        // Instructions
        $section->addText(
            'Petunjuk Pengisian:',
            ['bold' => true, 'size' => 11]
        );
        $section->addText('- Isi kolom Main Target dengan nomor urut (contoh: 1,2)', ['size' => 10]);
        $section->addText('- Isi kolom Target dengan nomor urut (contoh: 1,2)', ['size' => 10]);
        $section->addText('- Isi kolom Tanggal dengan format: Senin, 30 Maret 2026', ['size' => 10]);
        $section->addText('- Isi kolom Status dengan: Pembelajaran, Penilaian, Sumatif, atau Ditiadakan', ['size' => 10]);
        $section->addText('- Kolom Chapter dan Activity wajib diisi', ['size' => 10]);
        $section->addText('- Jika tidak ada pembelajaran, kosongkan baris tersebut', ['size' => 10]);

        $section->addTextBreak(1);

        // Tabel dengan header
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ];

        $phpWord->addTableStyle('JournalTable', $tableStyle);
        $table = $section->addTable('JournalTable');

        // Header row
        $headerRow = $table->addRow(400);
        $headerRow->addCell(500, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('No', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $headerRow->addCell(2500, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Tanggal', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $headerRow->addCell(2000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Main Target', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $headerRow->addCell(2000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Target', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $headerRow->addCell(3000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Chapter', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $headerRow->addCell(5000, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Activity', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $headerRow->addCell(2500, ['bgColor' => 'DDDDDD', 'vAlign' => 'center'])->addText('Status', ['bold' => true, 'size' => 10], ['alignment' => 'center']);

        // Month names - Indonesia
        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Data rows - pre-filled dengan tanggal
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $year.'-'.$month.'-'.$day);
            $dayName = $dayNames[$date->format('l')];
            $formattedDate = $dayName.', '.$day.' '.$monthNames[$month].' '.$year;

            $row = $table->addRow(300);
            $row->addCell(500, ['vAlign' => 'center'])->addText((string) $day, ['size' => 10], ['alignment' => 'center']);
            $row->addCell(3000, ['vAlign' => 'center'])->addText($formattedDate, ['size' => 10]);
            $row->addCell(2000, ['vAlign' => 'center'])->addText('', ['size' => 10]);
            $row->addCell(2000, ['vAlign' => 'center'])->addText('', ['size' => 10]);
            $row->addCell(3000, ['vAlign' => 'center'])->addText('', ['size' => 10]);
            $row->addCell(5000, ['vAlign' => 'center'])->addText('', ['size' => 10]);
            $row->addCell(2500, ['vAlign' => 'center'])->addText('', ['size' => 10]);
        }

        // Output
        $filename = 'journal_template_'.$subject->code.'_'.$monthNames[$month].'_'.$year.'.docx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'subject_id' => 'required',
            'grade_id' => 'required',
            'academic_year_id' => 'required',
            'file' => 'required|mimes:docx',
        ]);

        Log::info('Start journal upload process', [
            'user_id' => Auth::id(),
            'subject_id' => $request->subject_id,
            'grade_id' => $request->grade_id,
            'academic_year_id' => $request->academic_year_id,
        ]);

        $subject = Subject::findOrFail($request->subject_id);

        // Validasi subject milik user yang login
        if ($subject->user_id !== Auth::id()) {
            Log::warning('Unauthorized upload attempt', [
                'user_id' => Auth::id(),
                'subject_id' => $request->subject_id,
            ]);

            return response()->json(['message' => 'Unauthorized - Subject tidak milik Anda'], 403);
        }

        // Validasi grade sesuai subject
        if ($subject->grade_id != $request->grade_id) {
            Log::warning('Grade mismatch', [
                'subject_grade_id' => $subject->grade_id,
                'request_grade_id' => $request->grade_id,
            ]);

            return response()->json(['message' => 'Grade tidak sesuai dengan subject'], 400);
        }

        // Ambil Main Target dan Target yang valid untuk subject ini
        // Membuat mapping dari position (1,2,3...) ke ID
        $mainTargets = MainTarget::where('subject_id', $subject->id)
            ->orderBy('id')
            ->get();

        $targetRecords = Target::where('subject_id', $subject->id)
            ->orderBy('id')
            ->get();

        // Mapping position ke ID (position 1 = index 0)
        $mainTargetPositionToId = [];
        foreach ($mainTargets as $index => $mt) {
            $mainTargetPositionToId[$index + 1] = $mt->id;
        }

        $targetPositionToId = [];
        foreach ($targetRecords as $index => $t) {
            $targetPositionToId[$index + 1] = $t->id;
        }

        $totalMainTargets = count($mainTargets);
        $totalTargets = count($targetRecords);

        // Parse Word document
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($request->file('file')->getRealPath());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membaca file Word: '.$e->getMessage()], 400);
        }

        $journalsCreated = 0;
        $errors = [];

        Log::info('Processing Word document', [
            'total_sections' => count($phpWord->getSections()),
        ]);

        foreach ($phpWord->getSections() as $section) {
            $elements = $section->getElements();

            $subjectIdFromFile = null;
            $gradeIdFromFile = null;
            $monthFromFile = null;
            $mainTargetFromFile = null;
            $targetFromFile = null;
            $tableData = [];

            // Parse elements untuk mengambil header info dan tabel
            foreach ($elements as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text = $element->getText();

                    if (str_starts_with($text, 'Subject ID:')) {
                        $subjectIdFromFile = trim(str_replace('Subject ID:', '', $text));
                    } elseif (str_starts_with($text, 'Grade ID:')) {
                        $gradeIdFromFile = trim(str_replace('Grade ID:', '', $text));
                    } elseif (str_starts_with($text, 'Month:')) {
                        $monthFromFile = trim(str_replace('Month:', '', $text));
                    }
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $rows = $element->getRows();

                    // Skip header row (baris pertama)
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        $cells = $row->getCells();

                        if (count($cells) >= 7) {
                            $tableData[] = [
                                'no' => $this->getCellText($cells[0]),
                                'tanggal' => $this->getCellText($cells[1]),
                                'main_target' => $this->getCellText($cells[2]),
                                'target' => $this->getCellText($cells[3]),
                                'chapter' => $this->getCellText($cells[4]),
                                'activity' => $this->getCellText($cells[5]),
                                'status' => $this->getCellText($cells[6]),
                            ];
                        }
                    }
                }
            }

            // Validasi subject_id dan grade_id dari file
            if ($subjectIdFromFile && $subjectIdFromFile != $subject->id) {
                return response()->json(['message' => 'Subject ID dalam file tidak sesuai dengan subject yang dipilih'], 400);
            }

            if ($gradeIdFromFile && $gradeIdFromFile != $subject->grade_id) {
                return response()->json(['message' => 'Grade ID dalam file tidak sesuai dengan grade subject'], 400);
            }

            // Process setiap baris tabel
            foreach ($tableData as $row) {
                // Skip jika tanggal kosong DAN activity kosong
                if (empty(trim($row['tanggal'])) && empty(trim($row['activity']))) {
                    continue;
                }

                // Skip jika activity kosong
                if (empty(trim($row['activity']))) {
                    continue;
                }

                // Parse tanggal - multiple formats support
                $date = null;
                $tanggalStr = trim($row['tanggal']);

                Log::info('Parsing tanggal', ['input' => $tanggalStr]);

                // Try multiple formats
                $dateParsed = false;

                // Format 1: "Senin, 30 Maret 2026" or "Sabtu, 1 Maret 2025" -> extract "30 Maret 2026"
                if (preg_match('/(\d+)\s+(\w+)\s+\d{4}/', $tanggalStr, $matches)) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('d F Y', $matches[1].' '.$matches[2].' '.$matches[3]);
                        $dateParsed = true;
                        Log::info('Parsed with format 1 (Indonesian with day)', ['result' => $date->format('Y-m-d')]);
                    } catch (\Exception $e) {
                        // continue to next format
                    }
                }

                // Format 2: "30 Maret 2026" (tanpa nama hari)
                if (! $dateParsed && preg_match('/(\d+)\s+(\w+)\s+(\d{4})/', $tanggalStr, $matches)) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('d F Y', $matches[1].' '.$matches[2].' '.$matches[3]);
                        $dateParsed = true;
                        Log::info('Parsed with format 2 (Indonesian no day)', ['result' => $date->format('Y-m-d')]);
                    } catch (\Exception $e) {
                        // continue to next format
                    }
                }

                // Format 3: English "March 30, 2026"
                if (! $dateParsed) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('F j, Y', $tanggalStr);
                        $dateParsed = true;
                        Log::info('Parsed with format 3 (English)', ['result' => $date->format('Y-m-d')]);
                    } catch (\Exception $e) {
                        // continue to next format
                    }
                }

                // Format 4: "30-03-2026" or "01-03-2025"
                if (! $dateParsed) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('d-m-Y', $tanggalStr);
                        $dateParsed = true;
                        Log::info('Parsed with format 4 (d-m-Y)', ['result' => $date->format('Y-m-d')]);
                    } catch (\Exception $e) {
                        // continue to next format
                    }
                }

                // Format 5: "2026-03-30" (ISO)
                if (! $dateParsed) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalStr);
                        $dateParsed = true;
                        Log::info('Parsed with format 5 (ISO)', ['result' => $date->format('Y-m-d')]);
                    } catch (\Exception $e) {
                        // continue to next format
                    }
                }

                if (! $dateParsed) {
                    $errors[] = 'Row '.$row['no'].': Tanggal "'.$tanggalStr.'" tidak valid';
                    Log::warning('Failed to parse tanggal', ['input' => $tanggalStr]);

                    continue;
                }

                // Parse main_target - position number ke ID
                $rowMainTargetIds = [];
                if (! empty(trim($row['main_target']))) {
                    $positions = array_map('trim', explode(',', $row['main_target']));
                    foreach ($positions as $pos) {
                        if (! isset($mainTargetPositionToId[$pos])) {
                            $errors[] = 'Row '.$row['no'].': Main Target position "'.$pos.'" tidak valid (max: '.$totalMainTargets.')';

                            continue 2;
                        }
                        $rowMainTargetIds[] = $mainTargetPositionToId[$pos];
                    }
                }

                // Parse target - position number ke ID
                $rowTargetIds = [];
                if (! empty(trim($row['target']))) {
                    $positions = array_map('trim', explode(',', $row['target']));
                    foreach ($positions as $pos) {
                        if (! isset($targetPositionToId[$pos])) {
                            $errors[] = 'Row '.$row['no'].': Target position "'.$pos.'" tidak valid (max: '.$totalTargets.')';

                            continue 2;
                        }
                        $rowTargetIds[] = $targetPositionToId[$pos];
                    }
                }

                // Parse status
                $status = TeachingStatusEnum::PEMBELAJARAN; // default
                if (! empty(trim($row['status']))) {
                    $statusStr = trim($row['status']);
                    try {
                        $status = TeachingStatusEnum::from($statusStr);
                    } catch (\ValueError $e) {
                        $errors[] = 'Row '.$row['no'].': Status "'.$statusStr.'" tidak valid. Gunakan: Pembelajaran, Penilaian, Sumatif, atau Ditiadakan';

                        continue;
                    }
                }

                // Create Journal - selalu buat baru meskipun ada tanggal yang sama
                Log::info('Creating journal for row', [
                    'row_no' => $row['no'],
                    'date' => $date->format('Y-m-d'),
                    'main_target_ids' => $rowMainTargetIds,
                    'target_ids' => $rowTargetIds,
                ]);

                Journal::create([
                    'academic_year_id' => $request->academic_year_id,
                    'subject_id' => $subject->id,
                    'grade_id' => $subject->grade_id,
                    'user_id' => Auth::id(),
                    'date' => $date->format('Y-m-d'),
                    'main_target_id' => $rowMainTargetIds,
                    'target_id' => $rowTargetIds,
                    'chapter' => trim($row['chapter']) ?: null,
                    'activity' => trim($row['activity']),
                    'status' => $status,
                ]);

                $journalsCreated++;
            }
        }

        if ($journalsCreated === 0 && count($errors) > 0) {
            Log::warning('Journal upload failed - no records created', [
                'user_id' => Auth::id(),
                'subject_id' => $subject->id,
                'errors' => $errors,
            ]);

            return response()->json([
                'message' => 'Gagal upload journal. Errors: '.implode('; ', $errors),
            ], 400);
        }

        $message = 'Berhasil upload '.$journalsCreated.' journal';
        if (count($errors) > 0) {
            Log::warning('Journal upload completed with errors', [
                'user_id' => Auth::id(),
                'subject_id' => $subject->id,
                'success_count' => $journalsCreated,
                'error_count' => count($errors),
                'errors' => $errors,
            ]);
            $message .= '. Warnings: '.implode('; ', $errors);
        } else {
            Log::info('Journal upload completed successfully', [
                'user_id' => Auth::id(),
                'subject_id' => $subject->id,
                'success_count' => $journalsCreated,
            ]);
        }

        return response()->json([
            'message' => $message,
            'count' => $journalsCreated,
        ]);
    }

    private function getCellText($cell)
    {
        $text = '';
        $elements = $cell->getElements();

        foreach ($elements as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $text .= $element->getText();
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $textElement) {
                    if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $textElement->getText();
                    }
                }
            }
        }

        return $text;
    }
}

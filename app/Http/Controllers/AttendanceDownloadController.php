<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\StatusAttendanceEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class AttendanceDownloadController extends Controller
{
    public function downloadAttendance(Request $request)
    {
        try {
            Log::info('Attendance download started', [
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            // Validasi input
            $request->validate([
                'id' => 'required|string',
                'month' => 'required|integer|min:1|max:12'
            ]);

            // Get current year for the month
            $year = $request->year ?? date('Y');
            
            // Get student and validate it's from my subjects
            $student = Student::find($request->id);
            
            if (!$student) {
                return response()->json([
                    'error' => 'Siswa tidak ditemukan'
                ], 404);
            }

            // Validate student is from my subjects
            // Get my subjects and their grades
            $mySubjects = Subject::mySubjects()->with('grade')->get();
            $myGradeIds = $mySubjects->pluck('grade_id')->unique();
            
            // Check if student belongs to any of my grades
            $studentInMyGrades = $student->grades()
                ->whereIn('grades.id', $myGradeIds)
                ->exists();
            
            if (!$studentInMyGrades) {
                return response()->json([
                    'error' => 'Siswa tidak ditemukan dalam daftar siswa Anda'
                ], 403);
            }

            // Get academic year active
            $academicYear = AcademicYear::active()->first();
            
            if (!$academicYear) {
                return response()->json([
                    'error' => 'Tahun ajaran aktif tidak ditemukan'
                ], 404);
            }

            // Get all days in the month
            $startDate = Carbon::create($year, $request->month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            
            // Adjust to academic year date range if needed
            if ($startDate->lt($academicYear->date_start)) {
                $startDate = $academicYear->date_start->copy();
            }
            if ($endDate->gt($academicYear->date_end)) {
                $endDate = $academicYear->date_end->copy();
            }

            // Get attendance records for this student in this month
            $attendances = Attendance::query()
                ->where('student_id', $student->id)
                ->whereMonth('date', $request->month)
                ->whereYear('date', $year)
                ->orderBy('date', 'asc')
                ->get()
                ->keyBy(function ($attendance) {
                    return $attendance->date->format('Y-m-d');
                });

            // Generate Word Document
            $phpWord = new PhpWord();
            \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
            $section = $phpWord->addSection();
            $header = $section->addHeader();
            $footer = $section->addFooter();

            // Title
            $section->addText(
                'Laporan Kehadiran Siswa',
                [
                    'alignment' => 'center',
                    'size' => 24,
                    'bold' => true,
                ]
            );

            $section->addTextBreak(1);

            // Student Identity
            $section->addText('Identitas Siswa:', ['bold' => true, 'size' => 14]);
            $section->addText('Nama: ' . $student->name, ['size' => 12]);
            $section->addText('NISN: ' . ($student->nisn ?? '-'), ['size' => 12]);
            $section->addText('NIS: ' . ($student->nis ?? '-'), ['size' => 12]);
            if ($student->birthday) {
                $monthNamesIndo = [
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember',
                ];
                $birthdayFormatted = $student->birthday->format('d') . ' ' . 
                    $monthNamesIndo[$student->birthday->month] . ' ' . $student->birthday->format('Y');
                $section->addText('Tanggal Lahir: ' . $birthdayFormatted, ['size' => 12]);
            }
            if ($student->city_born) {
                $section->addText('Tempat Lahir: ' . $student->city_born, ['size' => 12]);
            }
            if ($student->gender) {
                $section->addText('Jenis Kelamin: ' . $student->gender->getLabel(), ['size' => 12]);
            }

            $section->addTextBreak(2);

            // Table Title
            $monthNames = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];

            $monthName = $monthNames[$request->month];
            $section->addText(
                'Periode Kehadiran Selama Bulan ' . $monthName . ' ' . $year,
                [
                    'alignment' => 'center',
                    'size' => 14,
                    'bold' => true,
                ]
            );

            $section->addTextBreak(1);

            // Create attendance table
            $tableStyle = [
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80
            ];

            $cellStyle = [
                'valign' => 'center',
            ];

            $headerCellStyle = [
                'valign' => 'center',
                'bgColor' => 'D3D3D3',
            ];

            $table = $section->addTable($tableStyle);
            
            // Header row
            $table->addRow();
            $table->addCell(1000, $headerCellStyle)->addText('No', ['bold' => true, 'alignment' => 'center']);
            $table->addCell(3000, $headerCellStyle)->addText('Tanggal', ['bold' => true, 'alignment' => 'center']);
            $table->addCell(4000, $headerCellStyle)->addText('Keterangan', ['bold' => true, 'alignment' => 'center']);

            // Initialize counters
            $countSick = 0;
            $countLeave = 0;
            $countAbsent = 0;
            $countPresent = 0;
            $no = 1;

            // Day names in Indonesian
            $dayNames = [
                0 => 'Minggu',
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
            ];

            // Month names in Indonesian
            $monthNamesIndo = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];

            // Generate rows for each day in the month (skip Sunday)
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                // Skip Sunday (dayOfWeek: 0 = Sunday)
                if ($currentDate->dayOfWeek === Carbon::SUNDAY) {
                    $currentDate->addDay();
                    continue;
                }

                $dateKey = $currentDate->format('Y-m-d');
                $attendance = $attendances->get($dateKey);

                // Get day name in Indonesian
                $dayName = $dayNames[$currentDate->dayOfWeek];
                
                // Format date in Indonesian: "Senin, 15 Januari 2024"
                $formattedDate = $dayName . ', ' . $currentDate->format('d') . ' ' . 
                    $monthNamesIndo[$currentDate->month] . ' ' . $currentDate->format('Y');

                $table->addRow();
                $table->addCell(1000, $cellStyle)->addText($no, ['alignment' => 'center']);
                $table->addCell(3000, $cellStyle)->addText($formattedDate, ['alignment' => 'left']);
                
                if ($attendance) {
                    $statusLabel = $attendance->status->getLabel();
                    $table->addCell(4000, $cellStyle)->addText($statusLabel, ['alignment' => 'left']);
                    
                    // Count by status
                    switch ($attendance->status) {
                        case StatusAttendanceEnum::SICK:
                            $countSick++;
                            break;
                        case StatusAttendanceEnum::LEAVE:
                            $countLeave++;
                            break;
                        case StatusAttendanceEnum::ABSENT:
                            $countAbsent++;
                            break;
                    }
                } else {
                    $table->addCell(4000, $cellStyle)->addText('Masuk', ['alignment' => 'left']);
                    $countPresent++;
                }

                $no++;
                $currentDate->addDay();
            }

            $section->addTextBreak(2);

            // Summary
            $section->addText('Kesimpulan:', ['bold' => true, 'size' => 14]);
            $section->addText('Jumlah Sakit: ' . $countSick . ' hari', ['size' => 12]);
            $section->addText('Jumlah Izin: ' . $countLeave . ' hari', ['size' => 12]);
            $section->addText('Jumlah Alpa: ' . $countAbsent . ' hari', ['size' => 12]);
            $section->addText('Jumlah Masuk: ' . $countPresent . ' hari', ['size' => 12]);

            $section->addTextBreak(3);

            // Signature Table
            $this->addSignatureTable($section, $academicYear);

            // Header
            $header->addText('Laporan Kehadiran Siswa - ' . $student->name . ' | Bulan ' . $monthName . ' ' . $year);

            // Footer
            $footer->addPreserveText('Halaman {PAGE} dari {NUMPAGES}.', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

            // Generate filename
            $studentName = $this->cleanFilename($student->name ?? 'Siswa');
            $studentName = str_replace(' ', '_', $studentName);
            $filename = "Kehadiran_{$studentName}_{$monthName}_{$year}.docx";

            // Clean any existing output buffer
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Create streamed response for direct download
            return new StreamedResponse(function () use ($phpWord) {
                try {
                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                    $writer->save('php://output');
                } catch (\Exception $e) {
                    Log::error('Error during PhpWord save to output.', ['error' => $e->getMessage()]);
                }
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
                'Pragma' => 'public',
            ]);
        } catch (\Exception $e) {
            Log::error("Error generating attendance download", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat file laporan kehadiran: ' . $e->getMessage()
            ], 500);
        }
    }

    private function addSignatureTable($section, $academicYear)
    {
        $tableStyle = [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 80
        ];

        $cellStyle = [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF'
        ];

        $table = $section->addTable($tableStyle);
        $table->addRow();

        // Kolom pertama - Tanda tangan Kepala Madrasah
        $cell1 = $table->addCell(4500, $cellStyle);
        $cell1->addText('Mengetahui,', ['alignment' => 'center']);
        $cell1->addText('Kepala Madrasah', ['alignment' => 'center']);
        $cell1->addTextBreak(2);
        $cell1->addText($academicYear->headmaster_name, ['bold' => true, 'alignment' => 'center']);
        $cell1->addText('NIP. ' . ($academicYear->headmaster_nip ?? '-'), ['bold' => true, 'alignment' => 'center']);

        // Kolom kedua - Tanda tangan Wali Kelas
        $cell2 = $table->addCell(4500, $cellStyle);
        $cell2->addTextBreak(1);
        $cell2->addText('Mengetahui,', ['alignment' => 'center']);
        $cell2->addText('Wali Kelas', ['alignment' => 'center']);
        $cell2->addTextBreak(2);
        $cell2->addText(Auth::user()->name ?? '-', ['bold' => true, 'alignment' => 'center']);
        $cell2->addText('NIP. ' . (Auth::user()->nip ?? '-'), ['bold' => true, 'alignment' => 'center']);

        $section->addTextBreak(2);
    }

    private function cleanFilename($filename)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $filename);
    }
}


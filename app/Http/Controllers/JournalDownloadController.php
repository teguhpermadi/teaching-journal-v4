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

            // --- DEBUGGING: CREATE DUMMY DOCUMENT ---
            Log::info('--- DEBUG MODE: Creating a dummy Word document. ---');

            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            $section->addTitle('Tes Dokumen Dummy', 0);
            $section->addText(
                'Ini adalah file Word yang dibuat untuk tujuan debugging. ' .
                'Jika file ini dapat diunduh dan dibuka dengan benar, itu berarti proses pembuatan file dan mekanisme unduhan berfungsi.'
            );
            $section->addText(
                'Masalahnya kemungkinan besar terletak pada konten dinamis yang dimasukkan ke dalam dokumen (misalnya, data dari database, pemrosesan HTML, atau penyisipan gambar).'
            );
            $section->addTextBreak(1);
            $section->addText(
                'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus. Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor. Cras elementum ultrices diam. Maecenas ligula massa, varius a, semper congue, euismod non, mi. Proin porttitor, orci nec nonummy molestie, enim est eleifend mi, non fermentum diam nisl sit amet erat. Duis semper. Duis arcu massa, scelerisque vitae, consequat in, pretium a, enim. Pellentesque congue. Ut in risus volutpat libero pharetra tempor. Cras vestibulum bibendum augue. Praesent egestas leo in pede. Praesent blandit odio eu enim. Pellentesque sed dui ut augue blandit sodales. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Aliquam nibh. Mauris ac mauris sed pede pellentesque fermentum. Maecenas adipiscing ante non diam. Proin sed libero.'
            );

            $filename = 'dummy_document_test.docx';

            // Alternative Download Method: Save to temp file first
            $tempDir = storage_path('app/temp_journals');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
            }
            $tempFilePath = $tempDir . '/' . uniqid() . '_' . $filename;

            try {
                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($tempFilePath);
            } catch (\Exception $e) {
                Log::error('Failed to save temporary journal file.', [
                    'path' => $tempFilePath,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Gagal menyimpan file jurnal sementara.'], 500);
            }

            Log::info('Journal saved to temporary file, preparing download.', ['path' => $tempFilePath]);

            // Download the file and delete it after sending
            return response()->download($tempFilePath, $filename)->deleteFileAfterSend(true);

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

    private function cleanFilename($filename)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $filename);
    }
}

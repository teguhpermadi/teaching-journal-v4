<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecapJournalController extends Controller
{
    public function index()
    {
        return view('recap-journal.index');
    }

    public function show(Request $request)
    {
        // Set locale to Indonesian
        \Carbon\Carbon::setLocale('id');
        
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Query journals dalam periode
        $journals = Journal::withoutGlobalScope('sort')
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['user', 'subject', 'grade', 'signatures'])
            ->orderBy('date', 'asc')
            ->get();

        // Group by user, subject, grade dan hitung statistik
        $recapData = [];
        foreach ($journals as $journal) {
            $key = $journal->user_id . '_' . $journal->subject_id . '_' . $journal->grade_id;
            
            if (!isset($recapData[$key])) {
                $recapData[$key] = [
                    'user_name' => $journal->user->name ?? 'N/A',
                    'subject_name' => $journal->subject->name ?? 'N/A',
                    'grade_name' => $journal->grade->name ?? 'N/A',
                    'total' => 0,
                    'signed' => 0,
                    'unsigned' => 0,
                ];
            }

            $recapData[$key]['total']++;
            
            // Check if journal has signatures
            if ($journal->signatures->isNotEmpty()) {
                $recapData[$key]['signed']++;
            } else {
                $recapData[$key]['unsigned']++;
            }
        }

        // Sort by user name, then subject name
        usort($recapData, function ($a, $b) {
            $userCompare = strcmp($a['user_name'], $b['user_name']);
            if ($userCompare !== 0) {
                return $userCompare;
            }
            return strcmp($a['subject_name'], $b['subject_name']);
        });

        // Calculate totals
        $totalJournals = $journals->count();
        $totalSigned = $journals->filter(function ($journal) {
            return $journal->signatures->isNotEmpty();
        })->count();
        $totalUnsigned = $totalJournals - $totalSigned;

        // Get headmaster
        $headmaster = User::role('headmaster')->first();

        return view('recap-journal.show', [
            'recapData' => $recapData,
            'totalJournals' => $totalJournals,
            'totalSigned' => $totalSigned,
            'totalUnsigned' => $totalUnsigned,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'headmaster' => $headmaster,
        ]);
    }

    public function downloadRecap(Request $request)
    {
        try {
            Log::info('Journal recap download started', [
                'request_data' => $request->all(),
            ]);

            // Validasi input
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Query journals dalam periode
            // Remove global scope 'sort' to avoid conflict
            $journals = Journal::withoutGlobalScope('sort')
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['user', 'subject', 'grade', 'signatures'])
                ->orderBy('date', 'asc')
                ->get();

            if ($journals->isEmpty()) {
                return response()->json([
                    'error' => 'Tidak ada jurnal ditemukan untuk periode yang diberikan'
                ], 404);
            }

            // Group by user, subject, grade dan hitung statistik
            $recapData = [];
            foreach ($journals as $journal) {
                $key = $journal->user_id . '_' . $journal->subject_id . '_' . $journal->grade_id;
                
                if (!isset($recapData[$key])) {
                    $recapData[$key] = [
                        'user_name' => $journal->user->name ?? 'N/A',
                        'subject_name' => $journal->subject->name ?? 'N/A',
                        'grade_name' => $journal->grade->name ?? 'N/A',
                        'total' => 0,
                        'signed' => 0,
                        'unsigned' => 0,
                    ];
                }

                $recapData[$key]['total']++;
                
                // Check if journal has signatures
                if ($journal->signatures->isNotEmpty()) {
                    $recapData[$key]['signed']++;
                } else {
                    $recapData[$key]['unsigned']++;
                }
            }

            // Sort by user name, then subject name
            usort($recapData, function ($a, $b) {
                $userCompare = strcmp($a['user_name'], $b['user_name']);
                if ($userCompare !== 0) {
                    return $userCompare;
                }
                return strcmp($a['subject_name'], $b['subject_name']);
            });

            // Calculate totals
            $totalJournals = $journals->count();
            $totalSigned = $journals->filter(function ($journal) {
                return $journal->signatures->isNotEmpty();
            })->count();
            $totalUnsigned = $totalJournals - $totalSigned;

            // Get headmaster
            $headmaster = User::role('headmaster')->first();

            // Generate Word Document
            $phpWord = new PhpWord();
            \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
            $section = $phpWord->addSection([
                'marginTop' => 1440,
                'marginBottom' => 1440,
                'marginLeft' => 1440,
                'marginRight' => 1440,
            ]);

            // Title
            $section->addText(
                'REKAPITULASI JURNAL MENGAJAR',
                [
                    'alignment' => 'center',
                    'size' => 18,
                    'bold' => true,
                ]
            );

            $section->addTextBreak(1);

            // Periode
            $section->addText(
                'Periode: ' . \Carbon\Carbon::parse($startDate)->format('d F Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d F Y'),
                [
                    'alignment' => 'center',
                    'size' => 12,
                ]
            );

            $section->addTextBreak(2);

            // Table style
            $tableStyle = [
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80,
            ];

            $firstRowStyle = [
                'bgColor' => 'CCCCCC',
                'bold' => true,
            ];

            // Create table
            $table = $section->addTable($tableStyle);

            // Header row
            $table->addRow();
            $table->addCell(800, $firstRowStyle)->addText('No', ['bold' => true, 'size' => 10]);
            $table->addCell(2000, $firstRowStyle)->addText('Nama Guru', ['bold' => true, 'size' => 10]);
            $table->addCell(2000, $firstRowStyle)->addText('Mata Pelajaran', ['bold' => true, 'size' => 10]);
            $table->addCell(1500, $firstRowStyle)->addText('Kelas', ['bold' => true, 'size' => 10]);
            $table->addCell(1500, $firstRowStyle)->addText('Jumlah Jurnal', ['bold' => true, 'size' => 10]);
            $table->addCell(2000, $firstRowStyle)->addText('Sudah Ditandatangani', ['bold' => true, 'size' => 10]);
            $table->addCell(2000, $firstRowStyle)->addText('Belum Ditandatangani', ['bold' => true, 'size' => 10]);

            // Data rows
            $no = 1;
            foreach ($recapData as $data) {
                $table->addRow();
                $table->addCell(800)->addText((string)$no, ['size' => 10]);
                $table->addCell(2000)->addText($data['user_name'], ['size' => 10]);
                $table->addCell(2000)->addText($data['subject_name'], ['size' => 10]);
                $table->addCell(1500)->addText($data['grade_name'], ['size' => 10]);
                $table->addCell(1500)->addText((string)$data['total'], ['size' => 10]);
                $table->addCell(2000)->addText((string)$data['signed'], ['size' => 10]);
                $table->addCell(2000)->addText((string)$data['unsigned'], ['size' => 10]);
                $no++;
            }

            $section->addTextBreak(2);

            // Rekapitulasi
            $section->addText(
                'REKAPITULASI',
                [
                    'bold' => true,
                    'size' => 12,
                ]
            );

            $section->addTextBreak(1);

            $section->addText(
                'Jumlah Seluruh Jurnal yang Terkumpul: ' . $totalJournals,
                [
                    'size' => 11,
                ]
            );

            $section->addText(
                'Jumlah Jurnal yang Sudah Ditandatangani: ' . $totalSigned,
                [
                    'size' => 11,
                ]
            );

            $section->addText(
                'Jumlah Jurnal yang Belum Ditandatangani: ' . $totalUnsigned,
                [
                    'size' => 11,
                ]
            );

            $section->addTextBreak(3);

            // Signature section
            $section->addText(
                'Malang, ' . \Carbon\Carbon::now()->format('d F Y'),
                [
                    'size' => 11,
                ]
            );

            $section->addTextBreak(2);

            $section->addText(
                'Mengetahui,',
                [
                    'size' => 11,
                ]
            );

            $section->addText(
                'Kepala MI AR RIDLO',
                [
                    'size' => 11,
                ]
            );

            $section->addTextBreak(3);

            if ($headmaster) {
                $section->addText(
                    $headmaster->name,
                    [
                        'size' => 11,
                        'bold' => true,
                    ]
                );
            } else {
                $section->addText(
                    '_________________________',
                    [
                        'size' => 11,
                    ]
                );
            }

            // Generate filename
            $filename = 'Rekap_Jurnal_' . \Carbon\Carbon::parse($startDate)->format('Y-m-d') . '_' . \Carbon\Carbon::parse($endDate)->format('Y-m-d') . '.docx';

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
            Log::error("Error generating journal recap", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat membuat file rekap jurnal: ' . $e->getMessage()
            ], 500);
        }
    }
}


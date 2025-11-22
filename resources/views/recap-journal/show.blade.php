<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Jurnal Mengajar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
            }
            .print-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Header -->
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold mb-2">REKAPITULASI JURNAL MENGAJAR</h1>
                    <p class="text-lg">
                        Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} - 
                        {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="mb-4 no-print flex gap-2 justify-end">
                    <a href="{{ route('recap-journal.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        Kembali
                    </a>
                    <button onclick="window.print()" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">
                        Cetak
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">No</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Nama Guru</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Mata Pelajaran</th>
                                <th class="border border-gray-300 px-4 py-2 text-left font-bold">Kelas</th>
                                <th class="border border-gray-300 px-4 py-2 text-center font-bold">Jumlah Jurnal</th>
                                <th class="border border-gray-300 px-4 py-2 text-center font-bold">Sudah Ditandatangani</th>
                                <th class="border border-gray-300 px-4 py-2 text-center font-bold">Belum Ditandatangani</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recapData as $index => $data)
                                <tr class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $data['user_name'] }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $data['subject_name'] }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ $data['grade_name'] }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-center">{{ $data['total'] }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-center">{{ $data['signed'] }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-center">{{ $data['unsigned'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="border border-gray-300 px-4 py-4 text-center text-gray-500">
                                        Tidak ada data jurnal untuk periode yang dipilih
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Rekapitulasi -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-3">REKAPITULASI</h2>
                    <div class="space-y-2">
                        <p class="text-base">
                            <strong>Jumlah Seluruh Jurnal yang Terkumpul:</strong> {{ $totalJournals }}
                        </p>
                        <p class="text-base">
                            <strong>Jumlah Jurnal yang Sudah Ditandatangani:</strong> {{ $totalSigned }}
                        </p>
                        <p class="text-base">
                            <strong>Jumlah Jurnal yang Belum Ditandatangani:</strong> {{ $totalUnsigned }}
                        </p>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="mt-8 print-break">
                    <div class="mb-4">
                        <p class="text-base">
                            Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                        </p>
                    </div>
                    <div class="mb-4">
                        <p class="text-base">Mengetahui,</p>
                        <p class="text-base font-bold">Kepala MI AR RIDLO</p>
                    </div>
                    <div class="mt-12">
                        @if($headmaster)
                            <p class="text-base font-bold">{{ $headmaster->name }}</p>
                        @else
                            <p class="text-base">_________________________</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use App\Filament\Resources\Journals\Widgets\JournalWidget;
use App\Models\AcademicYear;
use App\Models\Journal;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ListJournals extends ListRecords
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('download')
                ->modalWidth('md')
                ->slideOver()
                ->label('Download')
                ->schema([
                    Hidden::make('academic_year_id')
                        ->default(AcademicYear::active()->first()->id),
                    Hidden::make('user_id')
                        ->default(Auth::id()),
                    Hidden::make('grade_id')
                        ->reactive(),
                    Select::make('subject_id')
                        ->options(
                            fn () => Subject::mySubjects()
                                ->get()
                                ->map(
                                    fn ($subject) => [
                                        'label' => $subject->code.' - '.$subject->grade->name,
                                        'value' => $subject->id,
                                    ]
                                )->pluck('label', 'value')
                        )
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('grade_id', Subject::find($state)->grade_id);
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    // Grid::make()
                    //     ->columns(2)
                    //     ->schema([
                    //         DatePicker::make('start_date')
                    //             ->label('Start Date'),
                    //         DatePicker::make('end_date')
                    //             ->label('End Date'),
                    //     ]),
                    Select::make('month')
                        ->options([
                            '1' => 'January',
                            '2' => 'February',
                            '3' => 'March',
                            '4' => 'April',
                            '5' => 'May',
                            '6' => 'June',
                            '7' => 'July',
                            '8' => 'August',
                            '9' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Redirect to download route for automatic Word download
                    return redirect()->route('download-journal', $data);
                }),
            Action::make('download_template')
                ->modalWidth('md')
                ->label('Download Template')
                ->schema([
                    Hidden::make('academic_year_id')
                        ->default(AcademicYear::active()->first()->id),
                    Hidden::make('user_id')
                        ->default(Auth::id()),
                    Hidden::make('grade_id')
                        ->reactive(),
                    Select::make('subject_id')
                        ->label('Subject')
                        ->options(
                            fn () => Subject::mySubjects()
                                ->get()
                                ->map(
                                    fn ($subject) => [
                                        'label' => $subject->code.' - '.$subject->grade->name,
                                        'value' => $subject->id,
                                    ]
                                )->pluck('label', 'value')
                        )
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $subject = Subject::find($state);
                            if ($subject) {
                                $set('grade_id', $subject->grade_id);
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('month')
                        ->label('Month')
                        ->options([
                            '1' => 'January',
                            '2' => 'February',
                            '3' => 'March',
                            '4' => 'April',
                            '5' => 'May',
                            '6' => 'June',
                            '7' => 'July',
                            '8' => 'August',
                            '9' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    return redirect()->route('download-journal-template', $data);
                }),
            Action::make('upload')
                ->modalHeading('Upload Journal')
                ->modalWidth('md')
                ->label('Upload')
                ->schema([
                    Select::make('upload_subject_id')
                        ->label('Subject')
                        ->options(
                            fn () => Subject::mySubjects()
                                ->get()
                                ->map(
                                    fn ($subject) => [
                                        'label' => $subject->code.' - '.$subject->grade->name,
                                        'value' => $subject->id,
                                    ]
                                )->pluck('label', 'value')
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Upload Word File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->disk('public')
                        ->directory('journal-uploads')
                        ->preserveFilenames()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $subject = Subject::find($data['upload_subject_id']);
                    $file = $data['file'] ?? null;

                    if (! $subject) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('Subject tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    if (! $file) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('File tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Get file path - handle various formats from Filament FileUpload
                    $filePath = null;

                    // Case 1: UploadedFile object
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $filePath = $file->getRealPath();
                    }
                    // Case 2: Array from Filament FileUpload
                    elseif (is_array($file)) {
                        if (isset($file['path'])) {
                            $filePath = storage_path('app/public/'.$file['path']);
                        } elseif (isset($file['name'])) {
                            $filePath = storage_path('app/public/'.$file['name']);
                            if (! file_exists($filePath)) {
                                $filePath = storage_path('app/'.$file['name']);
                            }
                        } elseif (isset($file['file'])) {
                            $filePath = storage_path('app/public/'.$file['file']);
                        }
                    }
                    // Case 3: String - check in journal-uploads directory
                    elseif (is_string($file)) {
                        // With directory('journal-uploads'), file is in public/journal-uploads/
                        $filePath = storage_path('app/public/journal-uploads/'.$file);
                        if (! file_exists($filePath)) {
                            // Try direct in public
                            $filePath = storage_path('app/public/'.$file);
                            if (! file_exists($filePath)) {
                                // Try without directory
                                $filePath = storage_path('app/'.$file);
                            }
                        }
                    }

                    if (! $filePath || ! file_exists($filePath)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('File tidak ditemukan. Path: '.($filePath ?? 'null'))
                            ->danger()
                            ->send();

                        return;
                    }

                    // Create a temporary request-like object for the controller
                    try {
                        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('Gagal membaca file Word: '.$e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    // Validasi subject milik user
                    if ($subject->user_id !== Auth::id()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('Subject tidak milik Anda')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Ambil Main Target dan Target - buat mapping position ke ID
                    $mainTargets = \App\Models\MainTarget::where('subject_id', $subject->id)
                        ->orderBy('id')
                        ->get();

                    $targets = \App\Models\Target::where('subject_id', $subject->id)
                        ->orderBy('id')
                        ->get();

                    // Mapping position (1,2,3...) ke ID
                    $mainTargetPositionToId = [];
                    foreach ($mainTargets as $index => $mt) {
                        $mainTargetPositionToId[$index + 1] = $mt->id;
                    }

                    $targetPositionToId = [];
                    foreach ($targets as $index => $t) {
                        $targetPositionToId[$index + 1] = $t->id;
                    }

                    $totalMainTargets = count($mainTargets);
                    $totalTargets = count($targets);

                    Log::info('Start journal upload process', [
                        'user_id' => Auth::id(),
                        'subject_id' => $subject->id,
                        'total_main_targets' => $totalMainTargets,
                        'total_targets' => $totalTargets,
                        'main_target_positions' => $mainTargetPositionToId,
                        'target_positions' => $targetPositionToId,
                    ]);

                    $journalsCreated = 0;
                    $errors = [];

                    foreach ($phpWord->getSections() as $section) {
                        $elements = $section->getElements();

                        $subjectIdFromFile = null;
                        $gradeIdFromFile = null;
                        $tableData = [];

                        foreach ($elements as $element) {
                            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                                $text = $element->getText();

                                if (str_starts_with($text, 'Subject ID:')) {
                                    $subjectIdFromFile = trim(str_replace('Subject ID:', '', $text));
                                } elseif (str_starts_with($text, 'Grade ID:')) {
                                    $gradeIdFromFile = trim(str_replace('Grade ID:', '', $text));
                                }
                            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                                $rows = $element->getRows();

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

                        // Validasi subject_id dan grade_id
                        if ($subjectIdFromFile && $subjectIdFromFile != $subject->id) {
                            $errors[] = 'Subject ID dalam file tidak sesuai';
                        }

                        if ($gradeIdFromFile && $gradeIdFromFile != $subject->grade_id) {
                            $errors[] = 'Grade ID dalam file tidak sesuai';
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

                            // Parse tanggal - format: "Senin, 30 Maret 2026" atau "30 Maret 2026" atau "March 30, 2026"
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
                            $status = \App\TeachingStatusEnum::PEMBELAJARAN;
                            if (! empty(trim($row['status']))) {
                                $statusStr = trim($row['status']);
                                try {
                                    $status = \App\TeachingStatusEnum::from($statusStr);
                                } catch (\ValueError $e) {
                                    $errors[] = 'Row '.$row['no'].': Status "'.$statusStr.'" tidak valid';

                                    continue;
                                }
                            }

                            // Create Journal
                            Log::info('Creating journal for row', [
                                'row_no' => $row['no'],
                                'date' => $date->format('Y-m-d'),
                                'main_target_ids' => $rowMainTargetIds,
                                'target_ids' => $rowTargetIds,
                            ]);

                            Journal::create([
                                'academic_year_id' => AcademicYear::active()->first()->id,
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

                    $message = 'Berhasil upload '.$journalsCreated.' journal';
                    if (count($errors) > 0) {
                        Log::warning('Journal upload completed with errors', [
                            'user_id' => Auth::id(),
                            'subject_id' => $subject->id,
                            'success_count' => $journalsCreated,
                            'error_count' => count($errors),
                            'errors' => $errors,
                        ]);

                        $message .= '. '.count($errors).' row gagal: '.implode('; ', array_slice($errors, 0, 5));
                        if (count($errors) > 5) {
                            $message .= '...';
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Upload dengan warning')
                            ->body($message)
                            ->warning()
                            ->send();
                    } else {
                        Log::info('Journal upload completed successfully', [
                            'user_id' => Auth::id(),
                            'subject_id' => $subject->id,
                            'success_count' => $journalsCreated,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Success')
                            ->body($message)
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $mySubjects = Subject::mySubjects()->get();

        $tabs = [];

        foreach ($mySubjects as $subject) {
            // add subject code and grade name to tabs
            $tabs[$subject->code.' | '.$subject->grade->name] = Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subject_id', $subject->id))
                ->badge(fn () => Journal::where('subject_id', $subject->id)->count());
        }

        return $tabs;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JournalWidget::class,
            // \App\Filament\Resources\Journals\Widgets\JournalSignatureStatsWidget::class,
        ];
    }

    private function getCellText(\PhpOffice\PhpWord\Element\Cell $cell)
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

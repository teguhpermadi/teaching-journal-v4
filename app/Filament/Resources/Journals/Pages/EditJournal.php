<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class EditJournal extends EditRecord
{
    protected static string $resource = JournalResource::class;
    public array $attendanceData = [];

    protected function getHeaderActions(): array
    {
        $actions = [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];

        $journal = $this->record;
        $user = Auth::user();

        // Add sign as owner action if user is the owner
        if ($journal && $user && $journal->user_id === $user->id && !$journal->isSignedBy('owner')) {
            $actions[] = Action::make('signAsOwner')
                ->label('Tandatangani sebagai Pemilik')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->modalHeading('Tandatangani Journal')
                ->modalDescription('Silakan tanda tangani journal ini sebagai pemilik.')
                ->modalWidth('lg')
                ->form([
                    SignaturePad::make('signature')
                        ->label('Tanda Tangan')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->signAsOwner($data['signature']);
                        Notification::make()
                            ->title('Berhasil')
                            ->success()
                            ->body('Journal berhasil ditandatangani sebagai pemilik.')
                            ->send();
                        $this->refreshFormData(['signatures']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                });
        }

        // Add sign as headmaster action if user has headmaster role
        if ($journal && $user && $user->hasRole('headmaster') && !$journal->isSignedBy('headmaster')) {
            $actions[] = Action::make('signAsHeadmaster')
                ->label('Tandatangani sebagai Kepala Sekolah')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->modalHeading('Tandatangani Journal sebagai Kepala Sekolah')
                ->modalDescription('Silakan tanda tangani journal ini sebagai kepala sekolah.')
                ->modalWidth('lg')
                ->form([
                    SignaturePad::make('signature')
                        ->label('Tanda Tangan')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->signAsHeadmaster($data['signature']);
                        Notification::make()
                            ->title('Berhasil')
                            ->success()
                            ->body('Journal berhasil ditandatangani sebagai kepala sekolah.')
                            ->send();
                        $this->refreshFormData(['signatures']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                });
        }

        return $actions;
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $date = $this->record->date;
        $subject = $this->record->subject;

        if ($subject) {
            $studentIds = \App\Models\Student::whereHas('grades', function ($q) use ($subject) {
                $q->where('grades.id', $subject->grade_id);
            })->pluck('id');

            $attendances = \App\Models\Attendance::whereIn('student_id', $studentIds)
                ->where('date', $date)
                ->get();

            $data['attendance'] = $attendances->map(fn($item) => [
                'student_id' => $item->student_id,
                'status' => $item->status,
                'date' => $item->date,
            ])->toArray();
        } else {
            $data['attendance'] = [];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Simpan data attendance ke property sementara dan hapus dari data utama
        $this->attendanceData = $data['attendance'] ?? [];
        unset($data['attendance']);

        return $data;
    }

    protected function afterSave(): void
    {
        $date = $this->record->date;
        $subject = $this->record->subject;

        if ($subject) {
            // Get all students in this grade
            $allStudentIds = \App\Models\Student::whereHas('grades', function ($q) use ($subject) {
                $q->where('grades.id', $subject->grade_id);
            })->pluck('id')->toArray();

            // Get student IDs from the form
            $formStudentIds = collect($this->attendanceData)
                ->pluck('student_id')
                ->filter()
                ->toArray();

            // Delete attendance for students that were removed from the form
            // (students in grade but not in form)
            $studentsToDelete = array_diff($allStudentIds, $formStudentIds);

            if (!empty($studentsToDelete)) {
                \App\Models\Attendance::whereIn('student_id', $studentsToDelete)
                    ->where('date', $date)
                    ->delete();
            }

            // Update or create attendance for students in the form
            foreach ($this->attendanceData as $attendance) {
                if (!empty($attendance['student_id']) && !empty($attendance['status'])) {
                    \App\Models\Attendance::updateOrCreate(
                        [
                            'student_id' => $attendance['student_id'],
                            'date' => $date,
                        ],
                        [
                            'status' => $attendance['status'],
                        ]
                    );
                }
            }
        }
    }
}

<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use App\TeachingStatusEnum;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    public array $attendanceData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan data attendance ke property sementara dan hapus dari data utama
        $this->attendanceData = $data['attendance'] ?? [];
        unset($data['attendance']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // jika status ditiadakan maka setting chapter menjadi '-'
        if ($data['status'] == TeachingStatusEnum::DITIADAKAN) {
            $data['chapter'] = '-';
            $data['activity'] = '-';
        }

        return static::getModel()::create($data);
    }

    protected function afterCreate(): void
    {
        // Simpan data attendance
        foreach ($this->attendanceData as $attendance) {
            if (!empty($attendance['student_id']) && !empty($attendance['status'])) {
                \App\Models\Attendance::create([
                    'journal_id' => $this->record->id,
                    'student_id' => $attendance['student_id'],
                    'status' => $attendance['status'],
                    'date' => $this->record->date,
                ]);
            }
        }
    }
}

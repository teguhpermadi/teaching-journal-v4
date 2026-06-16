<?php

namespace App\Filament\Resources\AcademicEventResource\Pages;

use App\Filament\Resources\AcademicEventResource\AcademicEventResource;
use App\Models\AcademicYear;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAcademicEvent extends CreateRecord
{
    protected static string $resource = AcademicEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academic_year_id'] = AcademicYear::active()->first()->id;
        $data['user_id'] = Auth::id();

        return $data;
    }
}

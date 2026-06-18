<?php

namespace App\Filament\Resources\AcademicCalendars\Schemas;

use App\AcademicCalendarColorEnum;
use App\AcademicStatusCalendarEnum;
use App\Models\AcademicYear;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AcademicCalendarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Hidden::make('academic_year_id')
                    ->default(AcademicYear::active()->first()->id),
                TextInput::make('title')
                    ->label('Judul')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Masukkan judul kegiatan'),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->placeholder('Pilih tanggal'),
                Select::make('status')
                    ->label('Status')
                    ->options(AcademicStatusCalendarEnum::class)
                    ->default(AcademicStatusCalendarEnum::EFFECTIVE)
                    ->reactive()
                    ->required(),
                Select::make('color')
                    ->label('Warna')
                    ->native(false)
                    ->options(AcademicCalendarColorEnum::optionsWithPreview())
                    ->allowHtml()
                    ->default(fn (callable $get) => $get('status') === AcademicStatusCalendarEnum::EFFECTIVE->value
                        ? AcademicCalendarColorEnum::SAGE->value
                        : AcademicCalendarColorEnum::TOMATO->value
                    ),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}

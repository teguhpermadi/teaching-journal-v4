<?php

namespace App\Filament\Resources\AcademicEventResource\Schemas;

use App\AcademicEventType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AcademicEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipe Event')
                    ->options(AcademicEventType::class)
                    ->required(),

                TextInput::make('title')
                    ->label('Judul')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required(),

                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->afterOrEqual('start_date')
                    ->helperText('Kosongkan jika hanya 1 hari'),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('color')
                    ->label('Warna (opsional)')
                    ->type('color')
                    ->helperText('Biarkan kosong untuk menggunakan warna default berdasarkan tipe'),
            ]);
    }
}

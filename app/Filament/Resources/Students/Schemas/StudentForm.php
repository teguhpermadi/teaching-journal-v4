<?php

namespace App\Filament\Resources\Students\Schemas;

use App\GenderEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options(GenderEnum::class)
                    ->required(),
                TextInput::make('nisn')
                    ->label('NISN')
                    ->required()
                    ->unique()
                    ->numeric(),
                TextInput::make('nis')
                    ->label('NIS')
                    ->required()
                    ->unique()
                    ->numeric(),
                FileUpload::make('photo')
                    ->label('Foto')
                    ->avatar()
                    ->disk('public')
                    ->directory('photos')
                    ->visibility('public'),
            ]);
    }
}

<?php

namespace App\Filament\Pages;

use App\Settings\AcademicWeekSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageAcademicWeek extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static string $settings = AcademicWeekSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('min_non_effective_days')
                    ->label('Minimal Hari Tidak Efektif per Pekan')
                    ->helperText('Jika jumlah hari tidak efektif dalam satu pekan mencapai atau melebihi angka ini, maka pekan tersebut dianggap tidak efektif.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(6)
                    ->required(),
            ]);
    }
}

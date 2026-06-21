<?php

namespace App\Filament\Resources\AcademicCalendars\Tables;

use App\Models\AcademicCalendar;
use App\Services\AcademicCalendarSplitService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AcademicCalendarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('Warna'),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Tanggal')
                    ->formatStateUsing(fn ($record) => $record->start_date->format('D, d M Y').
                        ($record->end_date && ! $record->end_date->equalTo($record->start_date)
                            ? ' - '.$record->end_date->format('D, d M Y')
                            : ''))
                    ->sortable(['start_date']),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),
                TextColumn::make('academicYear.year')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                TextColumn::make('grades.name')
                    ->label('Kelas')
                    ->badge()
                    ->separator(','),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('lg')
                    ->after(function (HasTable $livewire) {
                        /** @var \Livewire\Component $livewire */
                        $livewire->dispatch('academic-calendar-updated');
                    }),
                Action::make('split')
                    ->label('Split')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Split Kegiatan')
                    ->modalDescription(fn (AcademicCalendar $record) => "Apakah Anda yakin ingin membagi kegiatan '{$record->title}' ({$record->start_date->format('d/m/Y')} - {$record->end_date->format('d/m/Y')}) menjadi "
                        .($record->start_date->diffInDays($record->end_date) + 1).' hari terpisah?'
                    )
                    ->modalSubmitActionLabel('Ya, Split')
                    ->action(function (AcademicCalendar $record, HasTable $livewire) {
                        try {
                            app(AcademicCalendarSplitService::class)->split($record);
                            /** @var \Livewire\Component $livewire */
                            $livewire->dispatch('academic-calendar-updated');
                            Notification::make()
                                ->title('Kegiatan berhasil di-split')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal split kegiatan')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (AcademicCalendar $record) => ! $record->start_date->equalTo($record->end_date)
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function (HasTable $livewire) {
                            /** @var \Livewire\Component $livewire */
                            $livewire->dispatch('academic-calendar-updated');
                        }),
                    ForceDeleteBulkAction::make()
                        ->after(function (HasTable $livewire) {
                            /** @var \Livewire\Component $livewire */
                            $livewire->dispatch('academic-calendar-updated');
                        }),
                    RestoreBulkAction::make()
                        ->after(function (HasTable $livewire) {
                            /** @var \Livewire\Component $livewire */
                            $livewire->dispatch('academic-calendar-updated');
                        }),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }
}

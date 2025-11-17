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
}

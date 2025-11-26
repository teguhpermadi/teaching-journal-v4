<?php

namespace App\Filament\Resources\Journals\Tables;

use App\Filament\Resources\Journals\RelationManagers\AttendanceRelationManager;
use App\Models\Journal;
use App\Models\Target;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\RelationManagerAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class JournalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('D, d M Y')
                    ->sortable(),
                // SpatieMediaLibraryImageColumn::make('activity_photos')
                //     ->label('Photos')
                //     ->collection('activity_photos')
                //     ->stacked()
                //     ->limit(3)
                //     ->circular(),
                // TextColumn::make('mainTarget.main_target')
                //     ->label('Main Target')
                //     ->limit(100)
                //     ->wrap()
                //     ->searchable(),
                TextColumn::make('chapter')
                    ->label('Chapter')
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                // TextColumn::make('target_id')
                //     ->label('Target')
                //     ->getStateUsing(function ($record) {
                //         return collect($record->target_id)->map(function ($target_id) {
                //             return Target::find($target_id)->target;
                //         });
                //     })
                //     ->wrap()
                //     ->bulleted()
                //     ->searchable(),
                TextColumn::make('attendance_count')
                    ->label('Attendance Count')
                    ->getStateUsing(function (Journal $record) {
                        // Get students in this grade
                        $studentIds = \App\Models\Student::whereHas('grades', function ($q) use ($record) {
                            $q->where('grades.id', $record->grade_id);
                        })->pluck('id');

                        // Count attendance for these students on this date
                        return \App\Models\Attendance::whereIn('student_id', $studentIds)
                            ->where('date', $record->date)
                            ->count();
                    }),
                TextColumn::make('owner_signature_status')
                    ->label('Tanda Tangan Guru')
                    ->badge()
                    ->getStateUsing(function (Journal $record) {
                        return $record->isSignedBy('owner') ? 'Sudah Ditandatangani' : 'Belum Ditandatangani';
                    })
                    ->color(function (Journal $record) {
                        return $record->isSignedBy('owner') ? 'success' : 'danger';
                    })
                    ->icon(function (Journal $record) {
                        return $record->isSignedBy('owner') ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withCount(['signatures as owner_signed' => function ($q) {
                            $q->where('signer_role', 'owner');
                        }])->orderBy('owner_signed', $direction);
                    }),
                TextColumn::make('headmaster_signature_status')
                    ->label('Tanda Tangan Kepala Sekolah')
                    ->badge()
                    ->getStateUsing(function (Journal $record) {
                        return $record->isSignedBy('headmaster') ? 'Sudah Ditandatangani' : 'Belum Ditandatangani';
                    })
                    ->color(function (Journal $record) {
                        return $record->isSignedBy('headmaster') ? 'success' : 'danger';
                    })
                    ->icon(function (Journal $record) {
                        return $record->isSignedBy('headmaster') ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withCount(['signatures as headmaster_signed' => function ($q) {
                            $q->where('signer_role', 'headmaster');
                        }])->orderBy('headmaster_signed', $direction);
                    }),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
                // RelationManagerAction::make('attendance-relation-manager')
                //     ->label('View Attendance')
                //     // ->slideOver()
                //     // ->modalWidth('lg')
                //     ->modalHeading('Attendance')
                //     ->modalDescription('Attendance for this journal')
                //     ->relationManager(AttendanceRelationManager::make())
                //     ->compact(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // Bulk sign as owner (for journal owners)
                    BulkAction::make('bulkSignAsOwner')
                        ->label('Tandatangani sebagai Pemilik (Bulk)')
                        ->icon('heroicon-o-pencil-square')
                        ->color('success')
                        ->modalHeading('Tandatangani Journal sebagai Pemilik')
                        ->modalDescription(
                            fn(Collection $records) =>
                            'Anda akan menandatangani ' . $records->count() . ' journal sebagai pemilik. Pastikan semua journal yang dipilih adalah milik Anda.'
                        )
                        ->modalWidth('lg')
                        ->form([
                            SignaturePad::make('signature')
                                ->label('Tanda Tangan')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $user = Auth::user();
                            $successCount = 0;
                            $failedCount = 0;
                            $errors = [];

                            foreach ($records as $journal) {
                                // Check if user is the owner
                                if ($journal->user_id !== $user->id) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}' bukan milik Anda.";
                                    continue;
                                }

                                // Check if already signed
                                if ($journal->isSignedBy('owner')) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}' sudah ditandatangani.";
                                    continue;
                                }

                                try {
                                    $journal->signAsOwner($data['signature'], $user);
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}': " . $e->getMessage();
                                }
                            }

                            // Show notification
                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->success()
                                    ->body("{$successCount} journal berhasil ditandatangani sebagai pemilik.")
                                    ->send();
                            }

                            if ($failedCount > 0) {
                                $errorMessage = "{$failedCount} journal gagal ditandatangani:\n" . implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $errorMessage .= "\n... dan " . (count($errors) - 5) . " lainnya.";
                                }

                                Notification::make()
                                    ->title('Peringatan')
                                    ->warning()
                                    ->body($errorMessage)
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    // Bulk sign as headmaster
                    BulkAction::make('bulkSignAsHeadmaster')
                        ->label('Tandatangani sebagai Kepala Sekolah (Bulk)')
                        ->icon('heroicon-o-check-circle')
                        ->color('primary')
                        ->modalHeading('Tandatangani Journal sebagai Kepala Sekolah')
                        ->modalDescription(
                            fn(Collection $records) =>
                            'Anda akan menandatangani ' . $records->count() . ' journal sebagai kepala sekolah.'
                        )
                        ->modalWidth('lg')
                        ->form([
                            SignaturePad::make('signature')
                                ->label('Tanda Tangan')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            /** @var \App\Models\User $user */
                            $user = Auth::user();

                            // Check if user has headmaster role
                            if (!$user || !$user->hasRole('headmaster')) {
                                Notification::make()
                                    ->title('Error')
                                    ->danger()
                                    ->body('Anda tidak memiliki izin untuk menandatangani sebagai kepala sekolah.')
                                    ->send();
                                return;
                            }

                            $successCount = 0;
                            $failedCount = 0;
                            $errors = [];

                            foreach ($records as $journal) {
                                // Check if already signed
                                if ($journal->isSignedBy('headmaster')) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}' sudah ditandatangani oleh kepala sekolah.";
                                    continue;
                                }

                                try {
                                    $journal->signAsHeadmaster($data['signature'], $user);
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}': " . $e->getMessage();
                                }
                            }

                            // Show notification
                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->success()
                                    ->body("{$successCount} journal berhasil ditandatangani sebagai kepala sekolah.")
                                    ->send();
                            }

                            if ($failedCount > 0) {
                                $errorMessage = "{$failedCount} journal gagal ditandatangani:\n" . implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $errorMessage .= "\n... dan " . (count($errors) - 5) . " lainnya.";
                                }

                                Notification::make()
                                    ->title('Peringatan')
                                    ->warning()
                                    ->body($errorMessage)
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->visible(function () {
                            /** @var \App\Models\User|null $user */
                            $user = Auth::user();
                            return $user && $user->hasRole('headmaster');
                        }),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // check if user role teacher
                // if (Auth::user()->hasRole('teacher')) {
                // }
                $query->myJournals();
            })
            ->poll('10s');
    }
}

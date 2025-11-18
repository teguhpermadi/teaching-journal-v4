<?php

namespace App\Filament\Resources\Signatures\Tables;

use App\Models\Journal;
use App\Models\MainTarget;
use App\Models\Target;
use App\TeachingStatusEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use App\Filament\Infolists\Components\ImageViewer;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class SignatureTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grade.name')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chapter')
                    ->label('Bab/Materi')
                    ->wrap()
                    ->searchable(),
                SpatieMediaLibraryImageColumn::make('activity_photos')
                    ->label('Foto')
                    ->collection('activity_photos')
                    ->stacked()
                    ->limit(3)
                    ->circular(),
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
                SelectFilter::make('grade_id')
                    ->label('Kelas')
                    ->relationship('grade', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user_id')
                    ->label('Guru')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('owner_signed')
                    ->label('Status Tanda Tangan Guru')
                    ->options([
                        'signed' => 'Sudah Ditandatangani',
                        'unsigned' => 'Belum Ditandatangani',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'signed') {
                            return $query->whereHas('signatures', function ($q) {
                                $q->where('signer_role', 'owner');
                            });
                        } elseif ($data['value'] === 'unsigned') {
                            return $query->whereDoesntHave('signatures', function ($q) {
                                $q->where('signer_role', 'owner');
                            });
                        }
                        return $query;
                    }),
                SelectFilter::make('headmaster_signed')
                    ->label('Status Tanda Tangan Kepala Sekolah')
                    ->options([
                        'signed' => 'Sudah Ditandatangani',
                        'unsigned' => 'Belum Ditandatangani',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'signed') {
                            return $query->whereHas('signatures', function ($q) {
                                $q->where('signer_role', 'headmaster');
                            });
                        } elseif ($data['value'] === 'unsigned') {
                            return $query->whereDoesntHave('signatures', function ($q) {
                                $q->where('signer_role', 'headmaster');
                            });
                        }
                        return $query;
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalHeading(fn(Journal $record) => 'Detail Journal - ' . $record->chapter)
                    ->schema(fn(Journal $record) => [
                        Grid::make()
                            ->schema([
                                TextEntry::make('date')
                                    ->date(),
                                TextEntry::make('status'),
                                TextEntry::make('subject.name'),
                                TextEntry::make('subject.grade.name'),
                                TextEntry::make('user.name'),
                            ])
                            ->columns(2),
                        TextEntry::make('chapter'),
                        TextEntry::make('activity')
                            ->html(),
                        TextEntry::make('notes')
                            ->html(),
                        ImageViewer::make('activity_photos')
                            ->label('Foto Kegiatan')
                            ->collection('activity_photos')
                            ->conversion('thumbnail')
                            ->gridColumns(2)
                            ->height('200px')
                            ->openOnClick(true)
                            ->visible($record->status !== \App\TeachingStatusEnum::DITIADAKAN)
                            ->images(function () use ($record) {
                                return $record->getMedia('activity_photos');
                            }),
                    ])
                    ->extraModalFooterActions(fn(Journal $record) => [
                        Action::make('signAsHeadmasterInModal')
                            ->label('Tandatangani sebagai Kepala Sekolah')
                            ->icon('heroicon-o-pencil-square')
                            ->color('primary')
                            ->visible(!$record->isSignedBy('headmaster'))
                            ->modalHeading('Tandatangani Journal sebagai Kepala Sekolah')
                            ->modalDescription('Silakan tanda tangani journal ini sebagai kepala sekolah.')
                            ->modalWidth('lg')
                            ->schema([
                                SignaturePad::make('signature')
                                    ->label('Tanda Tangan')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->action(function (Journal $record, array $data) {
                                try {
                                    $record->signAsHeadmaster($data['signature']);
                                    \Filament\Notifications\Notification::make()
                                        ->title('Berhasil')
                                        ->success()
                                        ->body('Journal berhasil ditandatangani sebagai kepala sekolah.')
                                        ->send();
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Error')
                                        ->danger()
                                        ->body($e->getMessage())
                                        ->send();
                                }
                            }),
                    ]),
                Action::make('signAsHeadmaster')
                    ->label('Tandatangani')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn(Journal $record) => !$record->isSignedBy('headmaster'))
                    ->modalHeading('Tandatangani Journal sebagai Kepala Sekolah')
                    ->modalDescription('Silakan tanda tangani journal ini sebagai kepala sekolah.')
                    ->modalWidth('lg')
                    ->schema([
                        SignaturePad::make('signature')
                            ->label('Tanda Tangan')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (Journal $record, array $data) {
                        try {
                            $record->signAsHeadmaster($data['signature']);
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->success()
                                ->body('Journal berhasil ditandatangani sebagai kepala sekolah.')
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('deleteOwnerSignature')
                    ->label('Hapus Tanda Tangan Guru')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(function (Journal $record) {
                        $user = Auth::user();
                        return $user && 
                               $record->isSignedBy('owner') && 
                               $record->user_id === $user->id;
                    })
                    ->modalHeading('Hapus Tanda Tangan Guru')
                    ->modalDescription('Apakah Anda yakin ingin menghapus tanda tangan guru? Journal akan tetap ada, hanya tanda tangannya yang dihapus.')
                    ->requiresConfirmation()
                    ->action(function (Journal $record) {
                        try {
                            $user = Auth::user();
                            $signature = $record->signatures()
                                ->where('signer_role', 'owner')
                                ->where('signer_id', $user->id)
                                ->first();
                            
                            if ($signature) {
                                $signature->delete();
                                \Filament\Notifications\Notification::make()
                                    ->title('Berhasil')
                                    ->success()
                                    ->body('Tanda tangan guru berhasil dihapus.')
                                    ->send();
                            } else {
                                throw new \Exception('Tanda tangan tidak ditemukan.');
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('deleteHeadmasterSignature')
                    ->label('Hapus Tanda Tangan Kepala Sekolah')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(function (Journal $record) {
                        $user = Auth::user();
                        return $user && 
                               $record->isSignedBy('headmaster') && 
                               $user->hasRole('headmaster');
                    })
                    ->modalHeading('Hapus Tanda Tangan Kepala Sekolah')
                    ->modalDescription('Apakah Anda yakin ingin menghapus tanda tangan kepala sekolah? Journal akan tetap ada, hanya tanda tangannya yang dihapus.')
                    ->requiresConfirmation()
                    ->action(function (Journal $record) {
                        try {
                            $user = Auth::user();
                            $signature = $record->signatures()
                                ->where('signer_role', 'headmaster')
                                ->where('signer_id', $user->id)
                                ->first();
                            
                            if ($signature) {
                                $signature->delete();
                                \Filament\Notifications\Notification::make()
                                    ->title('Berhasil')
                                    ->success()
                                    ->body('Tanda tangan kepala sekolah berhasil dihapus.')
                                    ->send();
                            } else {
                                throw new \Exception('Tanda tangan tidak ditemukan.');
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkSignAsHeadmaster')
                        ->label('Tandatangani sebagai Kepala Sekolah (Bulk)')
                        ->icon('heroicon-o-check-circle')
                        ->color('primary')
                        ->modalHeading('Tandatangani Journal sebagai Kepala Sekolah')
                        ->modalDescription(
                            fn($records) =>
                            'Anda akan menandatangani ' . $records->count() . ' journal sebagai kepala sekolah.'
                        )
                        ->modalWidth('lg')
                        ->form([
                            SignaturePad::make('signature')
                                ->label('Tanda Tangan')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->action(function ($records, array $data) {
                            $user = Auth::user();
                            $successCount = 0;
                            $failedCount = 0;
                            $errors = [];

                            foreach ($records as $journal) {
                                if ($journal->isSignedBy('headmaster')) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}' sudah ditandatangani.";
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

                            if ($successCount > 0) {
                                \Filament\Notifications\Notification::make()
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

                                \Filament\Notifications\Notification::make()
                                    ->title('Peringatan')
                                    ->warning()
                                    ->body($errorMessage)
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('bulkDeleteOwnerSignature')
                        ->label('Hapus Tanda Tangan Guru (Bulk)')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->modalHeading('Hapus Tanda Tangan Guru')
                        ->modalDescription(
                            fn($records) =>
                            'Anda akan menghapus tanda tangan guru dari ' . $records->count() . ' journal. Journal akan tetap ada, hanya tanda tangannya yang dihapus.'
                        )
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $user = Auth::user();
                            $successCount = 0;
                            $failedCount = 0;
                            $errors = [];

                            foreach ($records as $journal) {
                                // Only allow deletion if user is the journal owner
                                if ($journal->user_id !== $user->id) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}': Anda bukan pemilik journal ini.";
                                    continue;
                                }

                                if (!$journal->isSignedBy('owner')) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}': Belum ditandatangani oleh guru.";
                                    continue;
                                }

                                try {
                                    $signature = $journal->signatures()
                                        ->where('signer_role', 'owner')
                                        ->where('signer_id', $user->id)
                                        ->first();
                                    
                                    if ($signature) {
                                        $signature->delete();
                                        $successCount++;
                                    } else {
                                        $failedCount++;
                                        $errors[] = "Journal '{$journal->chapter}': Tanda tangan tidak ditemukan.";
                                    }
                                } catch (\Exception $e) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}': " . $e->getMessage();
                                }
                            }

                            if ($successCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Berhasil')
                                    ->success()
                                    ->body("{$successCount} tanda tangan guru berhasil dihapus.")
                                    ->send();
                            }

                            if ($failedCount > 0) {
                                $errorMessage = "{$failedCount} tanda tangan gagal dihapus:\n" . implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $errorMessage .= "\n... dan " . (count($errors) - 5) . " lainnya.";
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Peringatan')
                                    ->warning()
                                    ->body($errorMessage)
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulkDeleteHeadmasterSignature')
                        ->label('Hapus Tanda Tangan Kepala Sekolah (Bulk)')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->modalHeading('Hapus Tanda Tangan Kepala Sekolah')
                        ->modalDescription(
                            fn($records) =>
                            'Anda akan menghapus tanda tangan kepala sekolah dari ' . $records->count() . ' journal. Journal akan tetap ada, hanya tanda tangannya yang dihapus.'
                        )
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $user = Auth::user();
                            
                            if (!$user->hasRole('headmaster')) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->danger()
                                    ->body('Hanya kepala sekolah yang dapat menghapus tanda tangan kepala sekolah.')
                                    ->send();
                                return;
                            }

                            $successCount = 0;
                            $failedCount = 0;
                            $errors = [];

                            foreach ($records as $journal) {
                                if (!$journal->isSignedBy('headmaster')) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}': Belum ditandatangani oleh kepala sekolah.";
                                    continue;
                                }

                                try {
                                    $signature = $journal->signatures()
                                        ->where('signer_role', 'headmaster')
                                        ->where('signer_id', $user->id)
                                        ->first();
                                    
                                    if ($signature) {
                                        $signature->delete();
                                        $successCount++;
                                    } else {
                                        // Try to delete any headmaster signature if current user is headmaster
                                        $signature = $journal->signatures()
                                            ->where('signer_role', 'headmaster')
                                            ->first();
                                        
                                        if ($signature) {
                                            $signature->delete();
                                            $successCount++;
                                        } else {
                                            $failedCount++;
                                            $errors[] = "Journal '{$journal->chapter}': Tanda tangan tidak ditemukan.";
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $failedCount++;
                                    $errors[] = "Journal '{$journal->chapter}': " . $e->getMessage();
                                }
                            }

                            if ($successCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Berhasil')
                                    ->success()
                                    ->body("{$successCount} tanda tangan kepala sekolah berhasil dihapus.")
                                    ->send();
                            }

                            if ($failedCount > 0) {
                                $errorMessage = "{$failedCount} tanda tangan gagal dihapus:\n" . implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $errorMessage .= "\n... dan " . (count($errors) - 5) . " lainnya.";
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Peringatan')
                                    ->warning()
                                    ->body($errorMessage)
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('date', 'desc');
        // ->recordAction(
        //     Action::make('view')
        //         ->slideOver()
        //         ->modalHeading(fn (Journal $record) => 'Detail Journal - ' . $record->chapter)
        //         ->form(function (Journal $record) {
        //             return [
        //                 Placeholder::make('date')
        //                     ->label('Tanggal')
        //                     ->content(fn () => $record->date->format('d M Y')),
        //                 Placeholder::make('user.name')
        //                     ->label('Guru')
        //                     ->content(fn () => $record->user->name),
        //                 Placeholder::make('grade.name')
        //                     ->label('Kelas')
        //                     ->content(fn () => $record->grade->name),
        //                 Placeholder::make('subject.name')
        //                     ->label('Mata Pelajaran')
        //                     ->content(fn () => $record->subject->name . ' (' . $record->subject->code . ')'),
        //                 Placeholder::make('status')
        //                     ->label('Status')
        //                     ->content(fn () => $record->status->getLabel())
        //                     ->badge()
        //                     ->color(fn () => $record->status->getColor()),
        //                 Placeholder::make('main_targets')
        //                     ->label('Main Target')
        //                     ->content(function () use ($record) {
        //                         if (empty($record->main_target_id)) {
        //                             return '-';
        //                         }
        //                         $mainTargets = MainTarget::whereIn('id', $record->main_target_id)->get();
        //                         return $mainTargets->pluck('main_target')->join(', ');
        //                     })
        //                     ->visible(fn () => !empty($record->main_target_id)),
        //                 Placeholder::make('targets')
        //                     ->label('Target')
        //                     ->content(function () use ($record) {
        //                         if (empty($record->target_id)) {
        //                             return '-';
        //                         }
        //                         $targets = Target::whereIn('id', $record->target_id)->get();
        //                         return $targets->pluck('target')->join(', ');
        //                     })
        //                     ->visible(fn () => !empty($record->target_id)),
        //                 Placeholder::make('chapter')
        //                     ->label('Bab/Materi')
        //                     ->content(fn () => $record->chapter ?? '-')
        //                     ->visible(fn () => $record->status !== TeachingStatusEnum::DITIADAKAN),
        //                 Placeholder::make('activity')
        //                     ->label('Aktivitas')
        //                     ->content(fn () => $record->activity ? new \Illuminate\Support\HtmlString($record->activity) : '-')
        //                     ->visible(fn () => $record->status !== TeachingStatusEnum::DITIADAKAN),
        //                 Placeholder::make('activity_photos')
        //                     ->label('Foto Aktivitas')
        //                     ->content(function () use ($record) {
        //                         $photos = $record->getMedia('activity_photos');
        //                         if ($photos->isEmpty()) {
        //                             return 'Tidak ada foto';
        //                         }
        //                         $html = '<div class="grid grid-cols-3 gap-4 mt-2">';
        //                         foreach ($photos as $photo) {
        //                             $html .= '<img src="' . $photo->getUrl() . '" alt="Activity Photo" class="w-full h-auto rounded-lg object-cover" />';
        //                         }
        //                         $html .= '</div>';
        //                         return new \Illuminate\Support\HtmlString($html);
        //                     })
        //                     ->visible(fn () => $record->status !== TeachingStatusEnum::DITIADAKAN),
        //                 Placeholder::make('notes')
        //                     ->label('Catatan')
        //                     ->content(fn () => $record->notes ? new \Illuminate\Support\HtmlString($record->notes) : '-')
        //                     ->visible(fn () => !empty($record->notes)),
        //                 Placeholder::make('owner_signature')
        //                     ->label('Tanda Tangan Guru')
        //                     ->content(function () use ($record) {
        //                         $signature = $record->getOwnerSignature();
        //                         if (!$signature || !$signature->is_signed) {
        //                             return new \Illuminate\Support\HtmlString('<span class="text-danger-600 dark:text-danger-400">Belum ditandatangani</span>');
        //                         }
        //                         $html = '<div class="space-y-2">';
        //                         $html .= '<div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">';
        //                         $html .= '<img src="' . $signature->getSignatureDataUrl() . '" alt="Signature" class="max-w-full h-auto" />';
        //                         $html .= '</div>';
        //                         $html .= '<p class="text-sm text-gray-600 dark:text-gray-400">Ditandatangani oleh: ' . $signature->signer->name . '</p>';
        //                         $html .= '<p class="text-xs text-gray-500 dark:text-gray-500">Tanggal: ' . $signature->signed_at->format('d M Y H:i') . '</p>';
        //                         $html .= '</div>';
        //                         return new \Illuminate\Support\HtmlString($html);
        //                     }),
        //                 Placeholder::make('headmaster_signature')
        //                     ->label('Tanda Tangan Kepala Sekolah')
        //                     ->content(function () use ($record) {
        //                         $signature = $record->getHeadmasterSignature();
        //                         if (!$signature || !$signature->is_signed) {
        //                             return new \Illuminate\Support\HtmlString('<span class="text-danger-600 dark:text-danger-400">Belum ditandatangani</span>');
        //                         }
        //                         $html = '<div class="space-y-2">';
        //                         $html .= '<div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">';
        //                         $html .= '<img src="' . $signature->getSignatureDataUrl() . '" alt="Signature" class="max-w-full h-auto" />';
        //                         $html .= '</div>';
        //                         $html .= '<p class="text-sm text-gray-600 dark:text-gray-400">Ditandatangani oleh: ' . $signature->signer->name . '</p>';
        //                         $html .= '<p class="text-xs text-gray-500 dark:text-gray-500">Tanggal: ' . $signature->signed_at->format('d M Y H:i') . '</p>';
        //                         $html .= '</div>';
        //                         return new \Illuminate\Support\HtmlString($html);
        //                     }),
        //             ];
        //         })
        // );
    }
}

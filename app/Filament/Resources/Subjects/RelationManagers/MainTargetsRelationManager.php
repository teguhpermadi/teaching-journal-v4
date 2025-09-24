<?php

namespace App\Filament\Resources\Subjects\RelationManagers;

use App\Models\MainTarget;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MainTargetsRelationManager extends RelationManager
{
    protected static string $relationship = 'mainTargets';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Hidden::make('subject_id')
                    ->default($this->getOwnerRecord()->id),
                Hidden::make('grade_id')
                    ->default($this->getOwnerRecord()->grade_id),
                Hidden::make('academic_year_id')
                    ->default($this->getOwnerRecord()->academic_year_id),
                Textarea::make('main_target')
                    ->required()
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('main_target')
            ->columns([
                TextColumn::make('main_target')
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->action(function($data){
                        // Pecah string berdasarkan baris baru
                        // '\r\n' untuk Windows, '\n' untuk Unix/Linux/macOS
                        $lines = preg_split('/\r\n|\r|\n/', $data['main_target']);
                        foreach ($lines as $line) {
                            $trimmedLine = trim($line);
                    
                            // Pastikan baris tidak kosong sebelum menyimpan
                            if (!empty($trimmedLine)) {
                                MainTarget::create([
                                    'main_target' => $trimmedLine,
                                    'user_id' => Auth::id(),
                                    'subject_id' => $this->getOwnerRecord()->id,
                                    'grade_id' => $this->getOwnerRecord()->grade_id,
                                    'academic_year_id' => $this->getOwnerRecord()->academic_year_id,
                                ]);
                            }
                        }
                    }),
                // AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

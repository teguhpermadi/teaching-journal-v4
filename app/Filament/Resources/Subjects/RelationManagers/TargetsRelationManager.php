<?php

namespace App\Filament\Resources\Subjects\RelationManagers;

use App\Models\AcademicYear;
use App\Models\MainTarget;
use App\Models\Target;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TargetsRelationManager extends RelationManager
{
    protected static string $relationship = 'targets';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('academic_year_id')
                    ->default(AcademicYear::active()->first()->id),
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Hidden::make('subject_id')
                    ->default($this->getOwnerRecord()->id),
                Hidden::make('grade_id')
                    ->default($this->getOwnerRecord()->grade_id),
                Select::make('main_target_id')
                    ->options(function () {
                        return MainTarget::myMainTargetsInSubject($this->getOwnerRecord()->id)->pluck('main_target', 'id');
                    })
                    ->columnSpanFull()
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('target')
                    ->required()
                    ->label('Target')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('target')
            ->columns([
                TextColumn::make('mainTarget.main_target')
                    ->sortable()
                    ->wrap()
                    ->searchable(),
                TextColumn::make('target')
                    ->sortable()
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->action(function($data){
                        $lines = preg_split('/\r\n|\r|\n/', $data['target']);
                        foreach ($lines as $line) {
                            $trimmedLine = trim($line);
                    
                            // Pastikan baris tidak kosong sebelum menyimpan
                            if (!empty($trimmedLine)) {
                                Target::create([
                                    'target' => $trimmedLine,
                                    'user_id' => Auth::id(),
                                    'subject_id' => $this->getOwnerRecord()->id,
                                    'grade_id' => $this->getOwnerRecord()->grade_id,
                                    'academic_year_id' => $this->getOwnerRecord()->academic_year_id,
                                    'main_target_id' => $data['main_target_id'],
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

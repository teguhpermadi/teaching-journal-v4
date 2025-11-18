<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Permission;
use App\Models\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Section::make('Informasi Pengguna')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn ($livewire) => $livewire instanceof \App\Filament\Resources\Users\Pages\CreateUser)
                                    ->minLength(8)
                                    ->maxLength(255),
                            ])
                            ->columns(1),
                        Section::make('Roles & Permissions')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('roles')
                                    ->label('Roles')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->options(fn() => Role::query()->orderBy('name')->get()->pluck('name', 'ulid'))
                                    ->helperText('Pilih role yang akan diberikan kepada user'),
                                CheckboxList::make('permissions')
                                    ->label('Permissions')
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->gridDirection('row')
                                    ->columns(3)
                                    ->options(fn() => Permission::query()->orderBy('name')->get()->pluck('name', 'ulid'))
                                    ->helperText('Pilih permission yang akan diberikan kepada user'),
                            ])
                            ->columns(1)
                            ->collapsible(),
                    ])
                    ->columns(2),
            ]);
    }
}

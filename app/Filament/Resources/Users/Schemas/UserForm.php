<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->required()
                    ->email(),
                TextInput::make('password')
                    ->required()
                    ->minLength(8)
                    ->maxLength(255)
                    ->password(),
            ]);
    }
}

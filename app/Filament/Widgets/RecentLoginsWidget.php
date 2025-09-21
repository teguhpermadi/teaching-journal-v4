<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentLoginsWidget extends BaseWidget
{
    protected static ?string $heading = 'Login Terbaru';
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::whereNotNull('last_login_at')
                    ->orderBy('last_login_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama User')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->last_login_at?->format('d M Y, H:i:s')),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->status)
                    ->colors([
                        'success' => 'Online',
                        'warning' => 'Baru Saja',
                        'info' => 'Hari Ini',
                        'gray' => 'Offline',
                        'danger' => 'Belum Login',
                    ]),
            ])
            ->defaultSort('last_login_at', 'desc')
            ->paginated(false);
    }
}

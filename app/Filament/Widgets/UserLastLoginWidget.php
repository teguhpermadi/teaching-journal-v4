<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserLastLoginWidget extends BaseWidget
{
    
    protected function getStats(): array
    {
        $user = Auth::user();
        $lastLogin = $user->last_login_at;
        
        // Get recent users (last 7 days)
        $recentUsers = User::whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();
            
        // Get total users with login history
        $totalUsersWithLogin = User::whereNotNull('last_login_at')->count();
        
        // Get users logged in today
        $todayUsers = User::whereNotNull('last_login_at')
            ->whereDate('last_login_at', today())
            ->count();

        return [
            Stat::make('Login Terakhir Anda', $lastLogin ? $lastLogin->diffForHumans() : 'Belum pernah login')
                ->description($lastLogin ? $lastLogin->format('d M Y, H:i') : 'Tidak ada data')
                ->descriptionIcon('heroicon-m-clock')
                ->color($lastLogin ? 'success' : 'warning'),
                
            Stat::make('Pengguna Aktif Hari Ini', $todayUsers)
                ->description('Total login hari ini')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Pengguna Aktif 7 Hari', $recentUsers)
                ->description('Login dalam 7 hari terakhir')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('Total Pengguna Terdaftar', $totalUsersWithLogin)
                ->description('Yang pernah login')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),
        ];
    }
}

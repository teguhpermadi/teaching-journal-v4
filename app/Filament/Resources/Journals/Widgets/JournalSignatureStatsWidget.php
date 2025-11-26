<?php

namespace App\Filament\Resources\Journals\Widgets;

use App\Models\Journal;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class JournalSignatureStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $user = Auth::user();

        // Get all journals for the current user
        $myJournals = Journal::where('user_id', $user->id);

        // Count total journals
        $totalJournals = $myJournals->count();

        // Count journals signed by owner (teacher)
        $signedByOwner = Journal::where('user_id', $user->id)
            ->whereHas('signatures', function ($query) {
                $query->where('signer_role', 'owner');
            })
            ->count();

        // Count journals NOT signed by owner
        $unsignedByOwner = $totalJournals - $signedByOwner;

        // Count journals signed by headmaster
        $signedByHeadmaster = Journal::where('user_id', $user->id)
            ->whereHas('signatures', function ($query) {
                $query->where('signer_role', 'headmaster');
            })
            ->count();

        // Count journals NOT signed by headmaster
        $unsignedByHeadmaster = $totalJournals - $signedByHeadmaster;

        return [
            Stat::make('Total Jurnal', $totalJournals)
                ->icon('heroicon-o-document-text')
                ->iconPosition('start')
                ->description('Total jurnal yang Anda buat')
                ->descriptionIcon('heroicon-o-information-circle', 'before')
                ->color('primary')
                ->chartColor('primary'),

            Stat::make('Belum Ditandatangani Guru', $unsignedByOwner)
                ->icon('heroicon-o-x-circle')
                ->iconPosition('start')
                ->description('Jurnal yang belum Anda tandatangani')
                ->descriptionIcon('heroicon-o-exclamation-triangle', 'before')
                ->color('danger')
                ->chartColor('danger'),

            Stat::make('Sudah Ditandatangani Guru', $signedByOwner)
                ->icon('heroicon-o-check-circle')
                ->iconPosition('start')
                ->description('Jurnal yang sudah Anda tandatangani')
                ->descriptionIcon('heroicon-o-check-badge', 'before')
                ->color('success')
                ->chartColor('success'),

            Stat::make('Belum Ditandatangani Kepala Sekolah', $unsignedByHeadmaster)
                ->icon('heroicon-o-x-circle')
                ->iconPosition('start')
                ->description('Jurnal yang belum ditandatangani kepala sekolah')
                ->descriptionIcon('heroicon-o-exclamation-triangle', 'before')
                ->color('warning')
                ->chartColor('warning'),

            Stat::make('Sudah Ditandatangani Kepala Sekolah', $signedByHeadmaster)
                ->icon('heroicon-o-check-circle')
                ->iconPosition('start')
                ->description('Jurnal yang sudah ditandatangani kepala sekolah')
                ->descriptionIcon('heroicon-o-check-badge', 'before')
                ->color('info')
                ->chartColor('info'),
        ];
    }
}

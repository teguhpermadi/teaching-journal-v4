<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AcademicEventType: string implements HasColor, HasLabel
{
    case LIBUR_NASIONAL = 'libur_nasional';
    case LIBUR_SEKOLAH = 'libur_sekolah';
    case KEGIATAN_SEKOLAH = 'kegiatan_sekolah';
    case HARI_EFEKTIF = 'hari_efektif';
    case HARI_TIDAK_EFEKTIF = 'hari_tidak_efektif';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'Libur Nasional',
            self::LIBUR_SEKOLAH => 'Libur Sekolah',
            self::KEGIATAN_SEKOLAH => 'Kegiatan Sekolah',
            self::HARI_EFEKTIF => 'Hari Efektif',
            self::HARI_TIDAK_EFEKTIF => 'Hari Tidak Efektif',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LIBUR_NASIONAL => 'danger',
            self::LIBUR_SEKOLAH => 'warning',
            self::KEGIATAN_SEKOLAH => 'info',
            self::HARI_EFEKTIF => 'success',
            self::HARI_TIDAK_EFEKTIF => 'gray',
        };
    }

    public function getHexColor(): string
    {
        return match ($this) {
            self::LIBUR_NASIONAL => '#EF4444',
            self::LIBUR_SEKOLAH => '#F97316',
            self::KEGIATAN_SEKOLAH => '#3B82F6',
            self::HARI_EFEKTIF => '#22C55E',
            self::HARI_TIDAK_EFEKTIF => '#6B7280',
        };
    }

    public function isHoliday(): bool
    {
        return match ($this) {
            self::LIBUR_NASIONAL, self::LIBUR_SEKOLAH, self::HARI_TIDAK_EFEKTIF => true,
            default => false,
        };
    }
}

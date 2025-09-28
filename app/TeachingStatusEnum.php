<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum TeachingStatusEnum: string implements HasLabel
{
    case PEMBELAJARAN = 'Pembelajaran';
    case PENILAIAN = 'Penilaian';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PEMBELAJARAN => 'Pembelajaran',
            self::PENILAIAN => 'Penilaian',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PEMBELAJARAN => 'success',
            self::PENILAIAN => 'warning',
        };
    }
}

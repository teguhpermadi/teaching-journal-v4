<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum TeachingStatusEnum: string implements HasLabel
{
    case PEMBELAJARAN = 'Pembelajaran';
    case PENILAIAN = 'Formatif';
    case SUMATIF = 'Sumatif';
    case DITIADAKAN = 'Ditiadakan';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PEMBELAJARAN => 'Pembelajaran',
            self::PENILAIAN => 'Formatif',
            self::SUMATIF => 'Sumatif',
            self::DITIADAKAN => 'Pembelajaran Ditiadakan',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PEMBELAJARAN => 'success',
            self::PENILAIAN => 'warning',
            self::SUMATIF => 'secondary',
            self::DITIADAKAN => 'danger',
        };
    }
}

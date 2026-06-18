<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum AcademicStatusCalendarEnum: string implements HasLabel
{
    case EFFECTIVE = 'Efektif';
    case NOT_EFFECTIVE = 'Tidak Efektif';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EFFECTIVE => 'Efektif',
            self::NOT_EFFECTIVE => 'Tidak Efektif',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::EFFECTIVE => 'success',
            self::NOT_EFFECTIVE => 'danger',
        };
    }
}

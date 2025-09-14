<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusAttendanceEnum : string implements HasColor, HasLabel
{
    case ABSENT = 'Absent';
    case SICK = 'Sick';
    case LEAVE = 'Leave';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ABSENT => 'danger',
            self::SICK => 'warning',
            self::LEAVE => 'info',
        };
    }
}

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
        return match ($this) {
            self::ABSENT => 'Tanpa Keterangan',
            self::SICK => 'Sakit',
            self::LEAVE => 'Izin',
        };
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

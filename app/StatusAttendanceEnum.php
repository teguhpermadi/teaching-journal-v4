<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusAttendanceEnum implements HasColor, HasLabel
{
    case PRESENT;
    case ABSENT;
    case SICK;
    case LEAVE;

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::SICK => 'Sick',
            self::LEAVE => 'Leave',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::ABSENT => 'danger',
            self::SICK => 'warning',
            self::LEAVE => 'info',
        };
    }
}

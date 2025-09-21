<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum GenderEnum: string implements HasLabel
{
    case Male = 'Male';
    case Female = 'Female';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
        };
    }
}

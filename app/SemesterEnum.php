<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum SemesterEnum: string implements HasLabel
{
    case odd = 'ganjil';
    case even = 'genap';

    public function getLabel(): ?string
    {
        return match ($this) {
            SemesterEnum::odd => 'Ganjil',
            SemesterEnum::even => 'Genap',
        };
    }
}

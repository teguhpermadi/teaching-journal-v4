<?php

namespace App\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Livewire\Component;

class MyPersonalInfo extends PersonalInfo
{
    public array $only = [
        'name', 'email', 'telephone',
    ];

    protected function getTelephoneComponent(): TextInput
    {
        return TextInput::make('telephone')
            ->tel()
            ->label('Telephone');
    }

    protected function getProfileFormSchema(): array
    {
        $groupFields = Group::make([
            $this->getNameComponent(),
            $this->getEmailComponent(),
            $this->getTelephoneComponent(),
        ])->columnSpanFull();

        return ($this->hasAvatars)
            ? [filament('filament-breezy')->getAvatarUploadComponent(), $groupFields]
            : [$groupFields];
    }
}

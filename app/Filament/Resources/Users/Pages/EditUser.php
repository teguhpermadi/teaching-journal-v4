<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->record->roles->pluck('ulid')->toArray();
        $data['permissions'] = $this->record->permissions->pluck('ulid')->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove roles and permissions from data as they will be synced separately
        unset($data['roles'], $data['permissions']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (isset($this->data['roles'])) {
            $roles = \App\Models\Role::whereIn('ulid', $this->data['roles'])->get();
            $this->record->syncRoles($roles);
        }
        if (isset($this->data['permissions'])) {
            $permissions = \App\Models\Permission::whereIn('ulid', $this->data['permissions'])->get();
            $this->record->syncPermissions($permissions);
        }
    }
}

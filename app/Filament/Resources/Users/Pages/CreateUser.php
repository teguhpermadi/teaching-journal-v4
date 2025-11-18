<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove roles and permissions from data as they will be synced separately
        unset($data['roles'], $data['permissions']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (isset($this->data['roles']) && !empty($this->data['roles'])) {
            $roles = \App\Models\Role::whereIn('ulid', $this->data['roles'])->get();
            $this->record->syncRoles($roles);
        }
        if (isset($this->data['permissions']) && !empty($this->data['permissions'])) {
            $permissions = \App\Models\Permission::whereIn('ulid', $this->data['permissions'])->get();
            $this->record->syncPermissions($permissions);
        }
    }
}

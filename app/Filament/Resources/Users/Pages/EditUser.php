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
        
        // Get all permissions (direct + via roles) for display
        // But we'll only save direct permissions in afterSave()
        $allPermissions = $this->record->getAllPermissions();
        $data['permissions'] = $allPermissions->pluck('ulid')->toArray();

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
        // Sync roles first (this will update permissions via roles)
        if (isset($this->data['roles'])) {
            $roles = \App\Models\Role::whereIn('ulid', $this->data['roles'])->get();
            $this->record->syncRoles($roles);
        }
        
        // Get permissions that user selected in the form
        if (isset($this->data['permissions'])) {
            $selectedPermissions = \App\Models\Permission::whereIn('ulid', $this->data['permissions'])->get();
            
            // Get permissions that come from the new roles (after sync)
            $permissionsViaRoles = $this->record->getPermissionsViaRoles();
            $permissionsViaRolesUlids = $permissionsViaRoles->pluck('ulid')->toArray();
            
            // Filter: only sync direct permissions that are NOT from roles
            // Permissions from roles are automatically inherited, so we don't need to sync them
            $directPermissionsToSync = $selectedPermissions->filter(function ($permission) use ($permissionsViaRolesUlids) {
                // Only include permissions that are NOT inherited from roles
                return !in_array($permission->ulid, $permissionsViaRolesUlids);
            });
            
            // Sync only direct permissions (not inherited from roles)
            $this->record->syncPermissions($directPermissionsToSync);
        }
    }
}

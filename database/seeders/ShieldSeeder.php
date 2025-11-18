<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = [
            [
                'name' => 'admin',
                'guard_name' => 'web',
                'permissions' => [
                    'ViewAny:Role',
                    'View:Role',
                    'Create:Role',
                    'Update:Role',
                    'Delete:Role',
                    'Restore:Role',
                    'ForceDelete:Role',
                    'ForceDeleteAny:Role',
                    'RestoreAny:Role',
                    'Replicate:Role',
                    'Reorder:Role',
                    'ViewAny:User',
                    'View:User',
                    'Create:User',
                    'Update:User',
                    'Delete:User',
                    'Restore:User',
                    'ForceDelete:User',
                    'ForceDeleteAny:User',
                    'RestoreAny:User',
                    'Replicate:User',
                    'Reorder:User',
                    'ViewAny:AcademicYear',
                    'View:AcademicYear',
                    'Create:AcademicYear',
                    'Update:AcademicYear',
                    'Delete:AcademicYear',
                    'Restore:AcademicYear',
                    'ForceDelete:AcademicYear',
                    'ForceDeleteAny:AcademicYear',
                    'RestoreAny:AcademicYear',
                    'Replicate:AcademicYear',
                    'Reorder:AcademicYear',
                    'ViewAny:Grade',
                    'View:Grade',
                    'Create:Grade',
                    'Update:Grade',
                    'Delete:Grade',
                    'Restore:Grade',
                    'ForceDelete:Grade',
                    'ForceDeleteAny:Grade',
                    'RestoreAny:Grade',
                    'Replicate:Grade',
                    'Reorder:Grade',
                    'ViewAny:Subject',
                    'View:Subject',
                    'Create:Subject',
                    'Update:Subject',
                    'Delete:Subject',
                    'Restore:Subject',
                    'ForceDelete:Subject',
                    'ForceDeleteAny:Subject',
                    'RestoreAny:Subject',
                    'Replicate:Subject',
                    'Reorder:Subject',
                    'ViewAny:Student',
                    'View:Student',
                    'Create:Student',
                    'Update:Student',
                    'Delete:Student',
                    'Restore:Student',
                    'ForceDelete:Student',
                    'ForceDeleteAny:Student',
                    'RestoreAny:Student',
                    'Replicate:Student',
                    'Reorder:Student',
                    'ViewAny:Signature',
                    'View:Signature',
                    'Create:Signature',
                    'Update:Signature',
                    'Delete:Signature',
                    'Restore:Signature',
                    'ForceDelete:Signature',
                    'ForceDeleteAny:Signature',
                    'RestoreAny:Signature',
                    'Replicate:Signature',
                    'Reorder:Signature',
                ]
            ],
            [
                'name' => 'teacher',
                'guard_name' => 'web',
                'permissions' => [
                    'ViewAny:Subject',
                    'View:Subject',
                    'Create:Subject',
                    'Update:Subject',
                    'Delete:Subject',
                    'Restore:Subject',
                    'ForceDelete:Subject',
                    'ForceDeleteAny:Subject',
                    'RestoreAny:Subject',
                    'Replicate:Subject',
                    'Reorder:Subject',
                    'ViewAny:Journal',
                    'View:Journal',
                    'Create:Journal',
                    'Update:Journal',
                    'Delete:Journal',
                    'Restore:Journal',
                    'ForceDelete:Journal',
                    'ForceDeleteAny:Journal',
                    'RestoreAny:Journal',
                    'Replicate:Journal',
                    'Reorder:Journal',
                    'ViewAny:Transcript',
                    'View:Transcript',
                    'Create:Transcript',
                    'Update:Transcript',
                    'Delete:Transcript',
                    'Restore:Transcript',
                    'ForceDelete:Transcript',
                    'ForceDeleteAny:Transcript',
                    'RestoreAny:Transcript',
                    'Replicate:Transcript',
                    'Reorder:Transcript',
                    'ViewAny:Attendance',
                    'View:Attendance',
                    'Create:Attendance',
                    'Update:Attendance',
                    'Delete:Attendance',
                    'Restore:Attendance',
                    'ForceDelete:Attendance',
                    'ForceDeleteAny:Attendance',
                    'RestoreAny:Attendance',
                    'Replicate:Attendance',
                    'Reorder:Attendance'
                ]
            ],
            [
                'name' => 'headmaster',
                'guard_name' => 'web',
                'permissions' => [
                    'ViewAny:Role',
                    'View:Role',
                    'Create:Role',
                    'Update:Role',
                    'Delete:Role',
                    'Restore:Role',
                    'ForceDelete:Role',
                    'ForceDeleteAny:Role',
                    'RestoreAny:Role',
                    'Replicate:Role',
                    'Reorder:Role',
                    'ViewAny:Signature',
                    'View:Signature',
                    'Create:Signature',
                    'Update:Signature',
                ]
            ]
            // [
            //     'name' => 'super_admin',
            //     'guard_name' => 'web',
            //     'permissions' => [
            //         'ViewAny:Role',
            //         'View:Role',
            //         'Create:Role',
            //         'Update:Role',
            //         'Delete:Role',
            //         'Restore:Role',
            //         'ForceDelete:Role',
            //         'ForceDeleteAny:Role',
            //         'RestoreAny:Role',
            //         'Replicate:Role',
            //         'Reorder:Role'
            //     ]
            // ]
        ];

        static::makeRolesWithPermissions($rolesWithPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(array $rolesWithPermissions): void
    {
        if (! blank($rolesWithPermissions)) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolesWithPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

}

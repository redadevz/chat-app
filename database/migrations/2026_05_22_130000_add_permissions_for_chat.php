<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected string $guardName;

    protected array $permissions;

    protected array $roles;

    public function __construct()
    {
        $this->guardName = 'craftable-pro';

        $permissions = collect([
            'craftable-pro.chat.message-everyone',
            'craftable-pro.chat.message-client',
            'craftable-pro.chat.message-account-manager',
            'craftable-pro.chat.message-commercial',
            'craftable-pro.chat.message-delivery-agent',
            'craftable-pro.chat.message-super-admin',
        ]);

        $this->permissions = $permissions->map(fn ($p) => [
            'name'       => $p,
            'guard_name' => $this->guardName,
        ])->toArray();

        $this->roles = [
            [
                'name'        => 'Administrator',
                'guard_name'  => $this->guardName,
                'permissions' => $permissions,
            ],
        ];
    }

    public function up(): void
    {
        $tableNames = config('permission.table_names', [
            'roles'                => 'roles',
            'permissions'          => 'permissions',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        DB::transaction(function () use ($tableNames) {
            foreach ($this->permissions as $permission) {
                $exists = DB::table($tableNames['permissions'])->where([
                    'name'       => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ])->exists();

                if (! $exists) {
                    DB::table($tableNames['permissions'])->insert($permission);
                }
            }

            foreach ($this->roles as $role) {
                $permissionNames = $role['permissions'];
                unset($role['permissions']);

                $roleRow = DB::table($tableNames['roles'])->where([
                    'name'       => $role['name'],
                    'guard_name' => $role['guard_name'],
                ])->first();

                if ($roleRow === null) {
                    continue;
                }

                $permissionRows = DB::table($tableNames['permissions'])
                    ->whereIn('name', $permissionNames)
                    ->where('guard_name', $role['guard_name'])
                    ->get();

                foreach ($permissionRows as $permissionRow) {
                    $link = [
                        'permission_id' => $permissionRow->id,
                        'role_id'       => $roleRow->id,
                    ];

                    if (! DB::table($tableNames['role_has_permissions'])->where($link)->exists()) {
                        DB::table($tableNames['role_has_permissions'])->insert($link);
                    }
                }
            }
        });

        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names', [
            'permissions' => 'permissions',
        ]);

        DB::transaction(function () use ($tableNames) {
            foreach ($this->permissions as $permission) {
                DB::table($tableNames['permissions'])->where([
                    'name'       => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ])->delete();
            }
        });

        app()['cache']->forget(config('permission.cache.key'));
    }
};

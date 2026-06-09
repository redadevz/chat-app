<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
| The super-admin manages chat permissions from the Settings page, so it needs
| the same "settings.edit" permission the Settings page is gated by.
*/
return new class extends Migration
{
    protected string $guardName = 'craftable-pro';

    protected string $role = 'super-admin';

    protected string $permission = 'craftable-pro.settings.edit';

    public function up(): void
    {
        $roleId       = $this->roleId();
        $permissionId = $this->permissionId();

        if (! $roleId || ! $permissionId) {
            return;
        }

        $link = ['role_id' => $roleId, 'permission_id' => $permissionId];

        if (! DB::table('role_has_permissions')->where($link)->exists()) {
            DB::table('role_has_permissions')->insert($link);
        }

        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $roleId       = $this->roleId();
        $permissionId = $this->permissionId();

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')
                ->where(['role_id' => $roleId, 'permission_id' => $permissionId])
                ->delete();
        }

        app()['cache']->forget(config('permission.cache.key'));
    }

    private function roleId(): ?int
    {
        return DB::table('roles')
            ->where(['name' => $this->role, 'guard_name' => $this->guardName])
            ->value('id');
    }

    private function permissionId(): ?int
    {
        return DB::table('permissions')
            ->where(['name' => $this->permission, 'guard_name' => $this->guardName])
            ->value('id');
    }
};

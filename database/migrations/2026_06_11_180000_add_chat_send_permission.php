<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    private string $guard = 'craftable-pro';

    private string $permission = 'craftable-pro.chat.send';

    private array $roles = ['Administrator', 'super-admin', 'account-manager', 'client'];

    public function up(): void
    {
        if (! DB::table('permissions')->where(['name' => $this->permission, 'guard_name' => $this->guard])->exists()) {
            DB::table('permissions')->insert(['name' => $this->permission, 'guard_name' => $this->guard]);
        }

        $permissionId = $this->permissionId();

        foreach ($this->roles as $roleName) {
            $roleId = DB::table('roles')->where(['name' => $roleName, 'guard_name' => $this->guard])->value('id');

            if (! $roleId) {
                continue;
            }

            $link = ['permission_id' => $permissionId, 'role_id' => $roleId];

            if (! DB::table('role_has_permissions')->where($link)->exists()) {
                DB::table('role_has_permissions')->insert($link);
            }
        }

        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        if ($permissionId = $this->permissionId()) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app()['cache']->forget(config('permission.cache.key'));
    }

    private function permissionId(): ?int
    {
        return DB::table('permissions')
            ->where(['name' => $this->permission, 'guard_name' => $this->guard])
            ->value('id');
    }
};

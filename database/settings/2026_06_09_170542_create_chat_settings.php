<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('chat.max_message_length', 5000);
        $this->migrator->add('chat.whispers_enabled', true);
        $this->migrator->add('chat.default_visibility', 'public');
        $this->migrator->add('chat.message_default_type', 'text');

        $this->migrator->add('chat.roles', [
            'client'          => $this->roleId('client'),
            'admin'           => $this->roleId('Administrator'),
            'super_admin'     => $this->roleId('super-admin'),
            'account_manager' => $this->roleId('account-manager'),
            'staff'           => $this->roleIds(['Administrator', 'super-admin', 'account-manager']),
            'oversight'       => $this->roleIds(['super-admin', 'Administrator']),
        ]);

        $this->migrator->add('chat.visibility', [
            'public'   => 'public',
            'internal' => 'internal',
            'all'      => ['public', 'internal'],
        ]);

        $this->migrator->add('chat.channels', [
            'prefix'          => 'conversation',
            'internal_suffix' => '.internal',
            'user_prefix'     => 'whisper',
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('chat.max_message_length');
        $this->migrator->deleteIfExists('chat.whispers_enabled');
        $this->migrator->deleteIfExists('chat.default_visibility');
        $this->migrator->deleteIfExists('chat.message_default_type');
        $this->migrator->deleteIfExists('chat.roles');
        $this->migrator->deleteIfExists('chat.visibility');
        $this->migrator->deleteIfExists('chat.channels');
    }

    /** Resolve a craftable-pro role name to its id. */
    private function roleId(string $name): ?int
    {
        return DB::table('roles')
            ->where('name', $name)
            ->where('guard_name', 'craftable-pro')
            ->value('id');
    }

    /**
     * @param  array<string>  $names
     * @return array<int>
     */
    private function roleIds(array $names): array
    {
        return collect($names)
            ->map(fn (string $name) => $this->roleId($name))
            ->filter()
            ->values()
            ->all();
    }
};

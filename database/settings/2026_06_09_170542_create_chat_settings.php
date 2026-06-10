<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Seeded one-time from the current config/chat.php structure.
        $this->migrator->add('chat.max_message_length', 5000);
        $this->migrator->add('chat.whispers_enabled', true);
        $this->migrator->add('chat.default_visibility', 'public');
        $this->migrator->add('chat.message_default_type', config('chat.messages.default_type'));
        $this->migrator->add('chat.roles', config('chat.roles'));
        $this->migrator->add('chat.visibility', config('chat.visibility'));
        $this->migrator->add('chat.channels', config('chat.channels'));
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
};

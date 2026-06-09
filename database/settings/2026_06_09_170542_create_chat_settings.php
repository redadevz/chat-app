<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('chat.max_message_length', 5000);
        $this->migrator->add('chat.whispers_enabled', true);
        $this->migrator->add('chat.default_visibility', 'public');
    }

    public function down(): void
    {
        $this->migrator->delete('chat.max_message_length');
        $this->migrator->delete('chat.whisper   s_enabled');
        $this->migrator->delete('chat.default_visibility');
    }
};

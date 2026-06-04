<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateChatSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('chat.public_color', '#4f46e5');   // indigo-600
        $this->migrator->add('chat.internal_color', '#f59e0b'); // amber-500
    }

    public function down(): void
    {
        $this->migrator->delete('chat.public_color');
        $this->migrator->delete('chat.internal_color');
    }
}

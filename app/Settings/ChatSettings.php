<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;


class ChatSettings extends Settings
{
    public int $max_message_length;

    public bool $whispers_enabled;

    public string $default_visibility;

    public string $message_default_type;

    public array $roles;

    public array $visibility;

    public array $channels;

    public static function group(): string
    {
        return 'chat';
    }
}

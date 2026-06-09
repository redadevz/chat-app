<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ChatSettings extends Settings
{
    /** Maximum number of characters allowed in a single message. */
    public int $max_message_length;

    /** Whether private "whisper" replies are enabled. */
    public bool $whispers_enabled;

    /** Default visibility for new messages: "public" or "internal". */
    public string $default_visibility;

    public static function group(): string
    {
        return 'chat';
    }
}
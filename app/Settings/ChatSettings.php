<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * All chat configuration, stored in the database (editable at runtime) instead
 * of config/chat.php. config/chat.php now only provides the initial seed values
 * used by the settings migration.
 */
class ChatSettings extends Settings
{
    public int $max_message_length;

    public bool $whispers_enabled;

    public string $default_visibility;

    public string $message_default_type;

    /** client, admin, super_admin, account_manager, staff[], oversight[] */
    public array $roles;

    /** public, internal, all[] */
    public array $visibility;

    /** prefix, internal_suffix, user_prefix */
    public array $channels;

    public static function group(): string
    {
        return 'chat';
    }
}

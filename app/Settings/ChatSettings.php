<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ChatSettings extends Settings
{
    /** Colour of public / own messages and the "Public" controls (hex). */
    public string $public_color;

    /** Accent colour of internal notes & private replies (hex). */
    public string $internal_color;

    public static function group(): string
    {
        return 'chat';
    }
}

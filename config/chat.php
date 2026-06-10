<?php

/*
| The "staff" role set and the "all visibilities" list are derived from the
| individual values below so they can't drift out of sync. Staff can still be
| overridden explicitly with a comma-separated CHAT_ROLES_STAFF.
*/

$admin          = env('CHAT_ROLE_ADMIN', 'Administrator');
$superAdmin     = env('CHAT_ROLE_SUPER_ADMIN', 'super-admin');
$accountManager = env('CHAT_ROLE_ACCOUNT_MANAGER', 'account-manager');

$publicVisibility   = env('CHAT_VISIBILITY_PUBLIC', 'public');
$internalVisibility = env('CHAT_VISIBILITY_INTERNAL', 'internal');

return [



    'roles' => [
        'client'          => env('CHAT_ROLE_CLIENT', 'client'),
        'admin'           => $admin,
        'super_admin'     => $superAdmin,
        'account_manager' => $accountManager,

        'staff' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CHAT_ROLES_STAFF', "{$admin},{$superAdmin},{$accountManager}")),
        ))),

        'oversight' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CHAT_ROLES_OVERSIGHT', "{$superAdmin},{$admin}")),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message visibility
    |--------------------------------------------------------------------------
    | "public"  → the client can see the message.
    | "internal" → staff-only note (account-manager ↔ super-admin).
    */

    // The default visibility now lives in the database (App\Settings\ChatSettings).
    'visibility' => [
        'public'   => $publicVisibility,
        'internal' => $internalVisibility,
        'all'      => [$publicVisibility, $internalVisibility],
    ],

    /*
    |--------------------------------------------------------------------------
    | Realtime channels
    |--------------------------------------------------------------------------
    | Public messages broadcast on "{prefix}.{id}"; internal notes ride
    | "{prefix}.{id}{internal_suffix}" which only staff may subscribe to.
    */

    'channels' => [
        'prefix'          => env('CHAT_CHANNEL_PREFIX', 'conversation'),
        'internal_suffix' => env('CHAT_CHANNEL_INTERNAL_SUFFIX', '.internal'),
        // Per-user channel for private whispers — "{user_prefix}.{userId}".
        // Only the user themselves may subscribe, so a whisper never reaches
        // anyone but its two parties, even over the wire.
        'user_prefix'     => env('CHAT_CHANNEL_USER_PREFIX', 'whisper'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message rules
    |--------------------------------------------------------------------------
    */

    // max_length now lives in the database (App\Settings\ChatSettings).
    'messages' => [
        'default_type' => env('CHAT_MESSAGE_DEFAULT_TYPE', 'text'),
    ],

];

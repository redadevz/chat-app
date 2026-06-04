<?php

/*
| The "staff" role set and the "all visibilities" list are derived from the
| individual values below so they can't drift out of sync. Staff can still be
| overridden explicitly with a comma-separated CHAT_ROLES_STAFF.
*/

$superAdmin     = env('CHAT_ROLE_SUPER_ADMIN', 'super-admin');
$accountManager = env('CHAT_ROLE_ACCOUNT_MANAGER', 'account-manager');

$publicVisibility   = env('CHAT_VISIBILITY_PUBLIC', 'public');
$internalVisibility = env('CHAT_VISIBILITY_INTERNAL', 'internal');

return [

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    | Spatie role names this feature reasons about. "staff" is the set of
    | roles allowed to post/read internal notes and oversee conversations.
    */

    'roles' => [
        'client'          => env('CHAT_ROLE_CLIENT', 'client'),
        'super_admin'     => $superAdmin,
        'account_manager' => $accountManager,

        'staff' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CHAT_ROLES_STAFF', "{$superAdmin},{$accountManager}")),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message visibility
    |--------------------------------------------------------------------------
    | "public"  → the client can see the message.
    | "internal" → staff-only note (account-manager ↔ super-admin).
    */

    'visibility' => [
        'public'   => $publicVisibility,
        'internal' => $internalVisibility,
        'default'  => env('CHAT_VISIBILITY_DEFAULT', $publicVisibility),
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Message rules
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'max_length'   => (int) env('CHAT_MESSAGE_MAX_LENGTH', 5000),
        'default_type' => env('CHAT_MESSAGE_DEFAULT_TYPE', 'text'),
    ],

];

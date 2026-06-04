<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    | Spatie role names this feature reasons about. "staff" is the set of
    | roles allowed to post/read internal notes and oversee conversations.
    */

    'roles' => [
        'client'          => 'client',
        'super_admin'     => 'super-admin',
        'account_manager' => 'account-manager',

        'staff' => ['super-admin', 'account-manager'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message visibility
    |--------------------------------------------------------------------------
    | "public"  → the client can see the message.
    | "internal" → staff-only note (account-manager ↔ super-admin).
    */

    'visibility' => [
        'public'   => 'public',
        'internal' => 'internal',
        'default'  => 'public',
        'all'      => ['public', 'internal'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Realtime channels
    |--------------------------------------------------------------------------
    | Public messages broadcast on "{prefix}.{id}"; internal notes ride
    | "{prefix}.{id}{internal_suffix}" which only staff may subscribe to.
    */

    'channels' => [
        'prefix'          => 'conversation',
        'internal_suffix' => '.internal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Message rules
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'max_length'   => 5000,
        'default_type' => 'text',
    ],

];

<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['web', 'craftable-pro-base-middlewares', 'craftable-pro-auth-middleware'],
]);

// Every user listens on one personal channel — all messages and read receipts
// for them are delivered here. Server-side recipient selection (ChatController)
// enforces who may receive what, so this only needs to verify channel ownership.
Broadcast::channel('user.{userId}', fn ($user, int $userId) =>
    (int) $user->id === $userId
);

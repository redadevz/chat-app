<?php

use App\Models\Conversation;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['web', 'craftable-pro-base-middlewares', 'craftable-pro-auth-middleware'],
]);

$channels = app(ChatSettings::class)->channels;

// Conversation channel — members and oversight users.
Broadcast::channel($channels['prefix'].'.{conversationId}', fn ($user, int $conversationId) =>
    Conversation::find($conversationId)?->isVisibleTo($user) ?? false
);

// Staff-only internal-notes channel.
Broadcast::channel($channels['prefix'].'.{conversationId}'.$channels['internal_suffix'], fn ($user, int $conversationId) =>
    Conversation::find($conversationId)?->isInternalVisibleTo($user) ?? false
);

// Personal whisper channel — only the user themselves.
Broadcast::channel($channels['user_prefix'].'.{userId}', fn ($user, int $userId) =>
    (int) $user->id === $userId
);

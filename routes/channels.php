<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;


Broadcast::routes([
    'middleware' => ['web', 'craftable-pro-base-middlewares', 'craftable-pro-auth-middleware'],
]);

$channelPrefix = config('chat.channels.prefix');

Broadcast::channel($channelPrefix.'.{conversationId}', function ($user, int $conversationId) {
    return Conversation::find($conversationId)
        ?->members()
        ->where('craftable_pro_users.id', $user->id)
        ->exists()
        ?? false;
});

// Staff-only channel carrying internal notes. A subscriber must both be a
// member of the conversation AND hold a staff role — clients are excluded so
// they can never receive an internal message, even over the wire.
Broadcast::channel($channelPrefix.'.{conversationId}'.config('chat.channels.internal_suffix'), function ($user, int $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    $isMember = $conversation->members()
        ->where('craftable_pro_users.id', $user->id)
        ->exists();

    $isStaff = $user->roles->pluck('name')
        ->intersect(config('chat.roles.staff'))
        ->isNotEmpty();

    return $isMember && $isStaff;
});

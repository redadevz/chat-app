<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;


Broadcast::routes([
    'middleware' => ['web', 'craftable-pro-base-middlewares', 'craftable-pro-auth-middleware'],
]);

Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    return Conversation::find($conversationId)
        ?->members()
        ->where('craftable_pro_users.id', $user->id)
        ->exists()
        ?? false;
});

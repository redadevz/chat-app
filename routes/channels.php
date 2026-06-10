<?php

use App\Models\Conversation;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\Broadcast;


Broadcast::routes([
    'middleware' => ['web', 'craftable-pro-base-middlewares', 'craftable-pro-auth-middleware'],
]);

// Channel names come from the database (App\Settings\ChatSettings). Resolved
// once here, at channel-registration time.
$channels = app(ChatSettings::class)->channels;
$channelPrefix = $channels['prefix'];

Broadcast::channel($channelPrefix.'.{conversationId}', function ($user, int $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    $isMember = $conversation->members()
        ->where('craftable_pro_users.id', $user->id)
        ->exists();

    // Oversight users watch any conversation live without being a member.
    $isOversight = $user->roles->pluck('name')
        ->intersect(app(ChatSettings::class)->roles['oversight'])
        ->isNotEmpty();

    return $isMember || $isOversight;
});

// Staff-only channel carrying internal notes. A subscriber must hold a staff
// role AND either be a member or an oversight user — clients are excluded so
// they can never receive an internal message, even over the wire.
Broadcast::channel($channelPrefix.'.{conversationId}'.$channels['internal_suffix'], function ($user, int $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    $isMember = $conversation->members()
        ->where('craftable_pro_users.id', $user->id)
        ->exists();

    $roles       = $user->roles->pluck('name');
    $chatRoles   = app(ChatSettings::class)->roles;
    $isStaff     = $roles->intersect($chatRoles['staff'])->isNotEmpty();
    $isOversight = $roles->intersect($chatRoles['oversight'])->isNotEmpty();

    return $isStaff && ($isMember || $isOversight);
});

// Personal channel carrying private whispers. A user may only ever subscribe to
// their OWN channel, so a whisper addressed to someone else can never reach them
// — the wire-level guarantee behind "just between the two of you".
Broadcast::channel($channels['user_prefix'].'.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

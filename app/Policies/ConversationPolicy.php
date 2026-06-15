<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Settings\ChatSettings;
use Brackets\CraftablePro\Models\CraftableProUser;

class ConversationPolicy
{
    /** Staff and oversight use the admin chat; clients use the support widget instead. */
    public function viewAny(CraftableProUser $user): bool
    {
        return ! $this->isClient($user);
    }

    /** A thread is visible to its members and to oversight, never to clients. */
    public function view(CraftableProUser $user, Conversation $conversation): bool
    {
        return ! $this->isClient($user) && $conversation->isVisibleTo($user);
    }

    /** Posting needs the admin Messages permission, or membership/oversight on this thread. */
    public function sendMessage(CraftableProUser $user, Conversation $conversation): bool
    {
        return $user->can('craftable-pro.messages.create') || $conversation->isVisibleTo($user);
    }

    public function leave(CraftableProUser $user, Conversation $conversation): bool
    {
        return $conversation->hasMember($user);
    }

    /** Only clients use the support endpoint. */
    public function requestSupport(CraftableProUser $user): bool
    {
        return $this->isClient($user);
    }

    private function isClient(CraftableProUser $user): bool
    {
        return $user->hasRole(app(ChatSettings::class)->roles['client']);
    }
}

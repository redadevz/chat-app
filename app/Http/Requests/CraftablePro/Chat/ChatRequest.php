<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use App\Models\Conversation;
use App\Settings\ChatSettings;
use Brackets\CraftablePro\Models\CraftableProUser;
use Illuminate\Foundation\Http\FormRequest;

abstract class ChatRequest extends FormRequest
{
    protected function settings(): ChatSettings
    {
        return app(ChatSettings::class);
    }

    protected function isClient(CraftableProUser $user): bool
    {
        return $user->roles->pluck('name')->contains($this->settings()->roles['client']);
    }

    protected function isOversight(CraftableProUser $user): bool
    {
        return $user->roles->pluck('name')
            ->intersect($this->settings()->roles['oversight'])
            ->isNotEmpty();
    }

    protected function isMember(Conversation $conversation, int $userId): bool
    {
        return $conversation->members()
            ->where('craftable_pro_users.id', $userId)
            ->exists();
    }
}

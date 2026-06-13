<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use App\Models\Conversation;

class ShowChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        $user         = auth('craftable-pro')->user();
        $conversation = $this->route('conversation');

        if (! $user || ! $conversation instanceof Conversation) {
            return false;
        }

        if ($this->isClient($user)) {
            return false;
        }

        return $this->isOversight($user) || $this->isMember($conversation, $user->id);
    }

    public function rules(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use App\Models\Conversation;

class ShowChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        $user         = $this->authUser();
        $conversation = $this->route('conversation');

        return $user !== null
            && $conversation instanceof Conversation
            && ! $this->isClient($user)
            && $conversation->isVisibleTo($user);
    }
}

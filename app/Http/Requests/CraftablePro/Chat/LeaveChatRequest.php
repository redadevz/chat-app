<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use App\Models\Conversation;

class LeaveChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        $user         = $this->authUser();
        $conversation = $this->route('conversation');

        return $user !== null
            && $conversation instanceof Conversation
            && $conversation->hasMember($user);
    }
}

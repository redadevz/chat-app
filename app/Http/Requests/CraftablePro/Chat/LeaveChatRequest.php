<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use App\Models\Conversation;

class LeaveChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        $user         = auth('craftable-pro')->user();
        $conversation = $this->route('conversation');

        return $user !== null
            && $conversation instanceof Conversation
            && $this->isMember($conversation, $user->id);
    }

    public function rules(): array
    {
        return [];
    }
}

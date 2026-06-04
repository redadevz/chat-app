<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');
        $userId       = auth('craftable-pro')->id();

        return $conversation
            && $userId
            && $conversation->members()->where('craftable_pro_users.id', $userId)->exists();
    }

    public function rules(): array
    {
        $conversation = $this->route('conversation');

        return [
            'body'        => ['required', 'string', 'max:'.config('chat.messages.max_length')],
            'visibility'  => ['sometimes', Rule::in(config('chat.visibility.all'))],
            // A reply may only point at a message in the same conversation.
            'reply_to_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('messages', 'id')->where(
                    fn ($q) => $q->where('conversation_id', $conversation?->id),
                ),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Models\Conversation;
use Illuminate\Contracts\Validation\Validator;
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
            // A whisper may only be addressed to another member of this
            // conversation, never to yourself.
            'private_to_id' => [
                'sometimes', 'nullable', 'integer',
                'different:'.auth('craftable-pro')->id(),
                Rule::exists('conversation_members', 'user_id')->where(
                    fn ($q) => $q->where('conversation_id', $conversation?->id),
                ),
            ],
        ];
    }

    /**
     * Only an oversight user may start a whisper. A non-oversight member may
     * whisper back, but only to someone who has already whispered them here.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $recipientId = $this->input('private_to_id');

            if (! $recipientId) {
                return;
            }

            $user         = auth('craftable-pro')->user();
            $conversation = $this->route('conversation');

            $isOversight = $user->roles->pluck('name')
                ->intersect(config('chat.roles.oversight'))
                ->isNotEmpty();

            $whisperedFirst = $conversation->messages()
                ->where('user_id', $recipientId)
                ->where('private_to_id', $user->id)
                ->exists();

            if (! $isOversight && ! $whisperedFirst) {
                $validator->errors()->add(
                    'private_to_id',
                    'You may not privately message this member.',
                );
            }
        });
    }
}

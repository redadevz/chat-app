<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Message;

use App\Settings\ChatSettings;
use Brackets\CraftablePro\Models\CraftableProUser;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('sendMessage', $this->route('conversation'));
    }

    public function rules(): array
    {
        $conversation = $this->route('conversation');

        return [
            'body'        => ['required', 'string', 'max:'.app(ChatSettings::class)->max_message_length],
            'visibility'  => ['sometimes', Rule::in(app(ChatSettings::class)->visibility['all'])],
            'reply_to_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('messages', 'id')->where(
                    fn ($q) => $q->where('conversation_id', $conversation?->id),
                ),
            ],
            'private_to_id' => [
                'sometimes', 'nullable', 'integer',
                'different:'.auth('craftable-pro')->id(),
                Rule::exists('conversation_members', 'user_id')->where(
                    fn ($q) => $q->where('conversation_id', $conversation?->id),
                ),
            ],
        ];
    }


    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $id = $this->input('private_to_id');

            if (! $id) {
                return;
            }

            $user         = auth('craftable-pro')->user();
            $conversation = $this->route('conversation');

            if (! $conversation->whisperAllowedFrom($user, (int) $id)) {
                $validator->errors()->add(
                    'private_to_id',
                    'You may not privately message this member.',
                );

                return;
            }

            $recipient = CraftableProUser::find($id);

            if (! $recipient || ! $recipient->hasAnyRole(app(ChatSettings::class)->roles['staff'])) {
                $validator->errors()->add(
                    'private_to_id',
                    'You can only whisper staff members.',
                );
            }
        });
    }
}

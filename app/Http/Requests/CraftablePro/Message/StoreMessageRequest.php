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
        // Admin "Messages" CRUD page (and any messages.create holder).
        if (Gate::allows('craftable-pro.messages.create')) {
            return true;
        }

        $conversation = $this->route('conversation');
        $user         = auth('craftable-pro')->user();

        if (! $conversation || ! $user) {
            return false;
        }

        // Sending is controlled by a role-granted permission: remove the role,
        // lose the permission, and you can no longer text (still a member).
        if (! $user->can('craftable-pro.chat.send')) {
            return false;
        }

        $isMember = $conversation->members()
            ->where('craftable_pro_users.id', $user->id)
            ->exists();

        $isOversight = $user->roles->pluck('name')
            ->intersect(app(ChatSettings::class)->roles['oversight'])
            ->isNotEmpty();

        return $isMember || $isOversight;
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
            $staffRoles   = app(ChatSettings::class)->roles['staff'];

            $isStaff = $user->roles->pluck('name')
                ->intersect($staffRoles)
                ->isNotEmpty();

            $whisperedFirst = $conversation->messages()
                ->where('user_id', $id)
                ->where('private_to_id', $user->id)
                ->exists();

            if (! $isStaff && ! $whisperedFirst) {
                $validator->errors()->add(
                    'private_to_id',
                    'You may not privately message this member.',
                );

                return;
            }

            $recipient = CraftableProUser::find($id);
            $recipientIsStaff = $recipient
                && $recipient->roles->pluck('name')->intersect($staffRoles)->isNotEmpty();

            if (! $recipientIsStaff) {
                $validator->errors()->add(
                    'private_to_id',
                    'You can only whisper staff members.',
                );
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}

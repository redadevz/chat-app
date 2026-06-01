<?php

namespace App\Http\Requests\CraftablePro\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows("craftable-pro.messages.edit");
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'conversation_id' => ['sometimes'],
            'user_id' => ['nullable'],
            'reply_to_id' => ['nullable'],
            'body' => ['nullable'],
            'type' => ['sometimes', 'string'],
            
        ];
    }
}

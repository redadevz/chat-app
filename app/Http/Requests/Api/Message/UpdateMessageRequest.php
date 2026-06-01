<?php

namespace App\Http\Requests\Api\Message;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can("craftable-pro-api.messages.update");
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

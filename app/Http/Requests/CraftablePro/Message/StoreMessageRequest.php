<?php

namespace App\Http\Requests\CraftablePro\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows("craftable-pro.messages.create");
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'conversation_id' => ['required'],
            'user_id' => ['nullable'],
            'reply_to_id' => ['nullable'],
            'body' => ['nullable'],
            'type' => ['required', 'string'],
            
        ];
    }
}

<?php

namespace App\Http\Requests\CraftablePro\Conversation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows("craftable-pro.conversations.create");
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'created_by' => ['nullable'],
            'name' => ['nullable', 'string'],
            'type' => ['required', 'string'],
            
        ];
    }
}

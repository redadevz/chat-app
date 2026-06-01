<?php

namespace App\Http\Requests\CraftablePro\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows("craftable-pro.messages.index");
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer'],
            'bulk_select_all' => ['sometimes', 'boolean'],
            
        ];
    }
}

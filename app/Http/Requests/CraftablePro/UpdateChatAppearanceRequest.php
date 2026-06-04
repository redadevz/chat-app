<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateChatAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('craftable-pro.settings.edit');
    }

    public function rules(): array
    {
        return [
            'public_color'   => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'internal_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }
}

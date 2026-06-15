<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use Illuminate\Foundation\Http\FormRequest;

abstract class ChatRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use Illuminate\Support\Facades\Gate;

class ShowChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view', $this->route('conversation'));
    }
}

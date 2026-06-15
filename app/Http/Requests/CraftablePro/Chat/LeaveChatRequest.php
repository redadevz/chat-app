<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use Illuminate\Support\Facades\Gate;

class LeaveChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        return Gate::allows('leave', $this->route('conversation'));
    }
}

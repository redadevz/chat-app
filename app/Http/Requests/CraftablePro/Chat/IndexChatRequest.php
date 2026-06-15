<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Gate;

class IndexChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Conversation::class);
    }
}

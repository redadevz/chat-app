<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

class SupportChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        $user = auth('craftable-pro')->user();

        return $user !== null && $this->isClient($user);
    }

    public function rules(): array
    {
        return [];
    }
}

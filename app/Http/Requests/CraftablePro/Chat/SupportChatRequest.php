<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

class SupportChatRequest extends ChatRequest
{
    public function authorize(): bool
    {
        $user = $this->authUser();

        return $user !== null && $this->isClient($user);
    }
}

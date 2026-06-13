<?php

declare(strict_types=1);

namespace App\Http\Requests\CraftablePro\Chat;

use App\Settings\ChatSettings;
use Brackets\CraftablePro\Models\CraftableProUser;
use Illuminate\Foundation\Http\FormRequest;

abstract class ChatRequest extends FormRequest
{
    protected function authUser(): ?CraftableProUser
    {
        return auth('craftable-pro')->user();
    }

    protected function isClient(CraftableProUser $user): bool
    {
        return $user->hasRole(app(ChatSettings::class)->roles['client']);
    }

    public function rules(): array
    {
        return [];
    }
}

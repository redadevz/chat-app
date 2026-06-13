<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Brackets\CraftablePro\Models\CraftableProUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (Gate::allows('craftable-pro.chat.message-everyone')) {
            return true;
        }

        $target = CraftableProUser::find($this->input('user_id'));
        if (! $target) {
            return false;
        }

        $targetRole = $target->roles->first()?->name;
        if (! $targetRole) {
            return false;
        }

        return Gate::allows("craftable-pro.chat.message-{$targetRole}");
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:craftable_pro_users,id'],
        ];
    }
}

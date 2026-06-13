<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ChatPermissionRequest extends FormRequest
{
    /**
     * Only users who may edit Craftable PRO settings can view or change the
     * chat-permission matrix — the same gate the Settings page itself uses.
     */
    public function authorize(): bool
    {
        return (bool) auth('craftable-pro')->user()?->can('craftable-pro.settings.edit');
    }

    /**
     * Lenient by design so the same request serves the GET (no body) and the
     * PUT (the edited matrix).
     */
    public function rules(): array
    {
        return [
            'roles'           => ['sometimes', 'array'],
            'roles.*.id'      => ['required', 'integer'],
            'roles.*.perms'   => ['sometimes', 'array'],
            'roles.*.perms.*' => ['string'],
        ];
    }
}

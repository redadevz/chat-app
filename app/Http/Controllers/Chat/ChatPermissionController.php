<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ChatPermissionController extends Controller
{
    private const PREFIX = 'craftable-pro.chat.message-';

    public function index(): JsonResponse
    {
        $this->authorizeAccess();

        $columns = Permission::where('name', 'like', self::PREFIX.'%')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $p) => [
                'permission' => $p->name,
                'label'      => str_replace(self::PREFIX, '', $p->name),
            ]);

        $roles = Role::with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id'    => $role->id,
                'name'  => $role->name,
                'perms' => $role->permissions
                    ->pluck('name')
                    ->filter(fn (string $n) => str_starts_with($n, self::PREFIX))
                    ->values(),
            ]);

        return response()->json(['columns' => $columns, 'roles' => $roles]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'roles'         => ['array'],
            'roles.*.id'    => ['required', 'integer'],
            'roles.*.perms' => ['array'],
        ]);

        $chatPerms = Permission::where('name', 'like', self::PREFIX.'%')->pluck('name');

        foreach ($data['roles'] as $row) {
            $role = Role::find($row['id']);

            if (! $role) {
                continue;
            }

            // Keep the role's non-chat permissions, replace only the chat ones.
            $kept   = $role->permissions->pluck('name')->reject(fn (string $n) => str_starts_with($n, self::PREFIX));
            $chosen = collect($row['perms'])->intersect($chatPerms);

            $role->syncPermissions($kept->merge($chosen)->all());
        }

        return redirect()->back()->with(['message' => 'Chat permissions updated']);
    }

    private function authorizeAccess(): void
    {
        abort_unless(
            auth('craftable-pro')->user()?->can('craftable-pro.settings.edit'),
            403,
        );
    }
}

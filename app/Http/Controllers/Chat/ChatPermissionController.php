<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\CraftablePro\Chat\ChatPermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ChatPermissionController extends Controller
{
    private const PREFIX = 'craftable-pro.chat.message-';

    public function index(ChatPermissionRequest $request): JsonResponse
    {
        $columns = $this->chatPermissions()
            ->map(fn ($name) => [
                'permission' => $name,
                'label'      => str_replace(self::PREFIX, '', $name),
            ])->values();

        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id'    => $role->id,
                'name'  => $role->name,
                'perms' => $this->chatPermissionsOf($role)->values(),
            ]);

        return response()->json(['columns' => $columns, 'roles' => $roles]);
    }

    public function update(ChatPermissionRequest $request): RedirectResponse
    {
        $allowed = $this->chatPermissions();

        
        foreach ($request->validated('roles', []) as $row) {
            $role = Role::find($row['id']);

            if (! $role) {
                continue;
            }

            $kept   = $this->nonChatPermissionsOf($role);
            $chosen = collect($row['perms'] ?? [])->intersect($allowed);

            $role->syncPermissions($kept->merge($chosen)->all());
        }

        return redirect()->back()->with(['message' => ___('craftable-pro', 'Chat permissions updated')]);
    }

    private function chatPermissions(): Collection
    {
        return Permission::query()
            ->where('name', 'like', self::PREFIX.'%')
            ->orderBy('name')
            ->pluck('name');
    }

    private function chatPermissionsOf(Role $role)
    {
        return $role->permissions->pluck('name')
            ->filter(fn ($n) => str_starts_with($n, self::PREFIX));
    }

    private function nonChatPermissionsOf(Role $role)
    {
        return $role->permissions->pluck('name')
            ->reject(fn ($n) => str_starts_with($n, self::PREFIX));
    }
}

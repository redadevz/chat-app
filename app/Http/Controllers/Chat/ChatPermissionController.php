<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Lets oversight users (super-admin / Administrator) configure, from Settings,
 * which roles each role is allowed to start a chat with. It edits the
 * "craftable-pro.chat.message-{role}" permissions that StoreChatRequest reads.
 */
class ChatPermissionController extends Controller
{
    private const GUARD = 'craftable-pro';

    private const PREFIX = 'craftable-pro.chat.message-';

    public function index(): JsonResponse
    {
        $this->authorizeOversight();

        $columns = Permission::query()
            ->where('guard_name', self::GUARD)
            ->where('name', 'like', self::PREFIX.'%')
            ->get()
            ->sortBy(fn (Permission $p) => $p->name === self::PREFIX.'everyone' ? '' : $p->name)
            ->map(fn (Permission $p) => [
                'permission' => $p->name,
                'label'      => str_replace(self::PREFIX, '', $p->name),
            ])->values();

        $roles = Role::query()
            ->where('guard_name', self::GUARD)
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id'    => $role->id,
                'name'  => $role->name,
                'perms' => $role->permissions
                    ->pluck('name')
                    ->filter(fn (string $n) => str_starts_with($n, self::PREFIX))
                    ->values(),
            ])->values();

        return response()->json([
            'columns' => $columns,
            'roles'   => $roles,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeOversight();

        $validated = $request->validate([
            'roles'           => ['present', 'array'],
            'roles.*.id'      => ['required', 'integer'],
            'roles.*.perms'   => ['present', 'array'],
            'roles.*.perms.*' => ['string'],
        ]);

        // Whitelist of valid chat permission names → ids. Anything not in here
        // is ignored, so the request can never grant arbitrary permissions.
        $chatPermissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->where('name', 'like', self::PREFIX.'%')
            ->pluck('id', 'name');

        DB::transaction(function () use ($validated, $chatPermissions) {
            foreach ($validated['roles'] as $row) {
                $role = Role::query()
                    ->where('guard_name', self::GUARD)
                    ->find($row['id']);

                if (! $role) {
                    continue;
                }

                $desiredIds = collect($row['perms'])
                    ->filter(fn (string $name) => $chatPermissions->has($name))
                    ->map(fn (string $name) => $chatPermissions->get($name))
                    ->values();

                // Replace this role's chat permissions wholesale: drop all
                // chat.message-* links, then re-add only the chosen ones.
                DB::table('role_has_permissions')
                    ->where('role_id', $role->id)
                    ->whereIn('permission_id', $chatPermissions->values())
                    ->delete();

                $desiredIds->each(fn (int $permissionId) => DB::table('role_has_permissions')->insert([
                    'role_id'       => $role->id,
                    'permission_id' => $permissionId,
                ]));
            }
        });

        app()['cache']->forget(config('permission.cache.key'));

        return redirect()->back()->with(['message' => ___('craftable-pro', 'Chat permissions updated')]);
    }

    private function authorizeOversight(): void
    {
        $user = auth('craftable-pro')->user();

        abort_unless(
            $user && $user->roles->pluck('name')
                ->intersect(config('chat.roles.oversight'))
                ->isNotEmpty(),
            403,
        );
    }
}

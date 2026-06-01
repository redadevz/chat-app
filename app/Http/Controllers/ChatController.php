<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\Chat\StoreChatRequest;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use Brackets\CraftablePro\Models\CraftableProUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ChatController extends Controller
{
    public function index(): Response
    {
        $user = auth('craftable-pro')->user();

        // Clients use the floating popup only — they shouldn't see the full chat page.
        abort_if($user->roles->pluck('name')->contains('client'), 403);

        return $this->render();
    }

    public function show(Conversation $conversation): Response
    {
        $this->ensureMember($conversation);
        $this->markAsRead($conversation);

        return $this->render($conversation);
    }

    public function store(StoreChatRequest $request): RedirectResponse
    {
        $user  = auth('craftable-pro')->user();
        $other = $request->validated('user_id');

        $conversation = Conversation::findOrCreatePrivateBetween($user, $other);

        return redirect()->route('chats.show', $conversation);
    }

    public function storeMessage(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $message = $conversation->messages()->create([
            'user_id' => auth('craftable-pro')->id(),
            'body'    => $request->validated('body'),
            'type'    => 'text',
        ]);

        $conversation->touch();

        broadcast(new MessageSent($message->load('sender')))->toOthers();

        return redirect()->route('chats.show', $conversation);
    }


    private function render(?Conversation $active = null): Response
    {
        $user = auth('craftable-pro')->user();

        return Inertia::render('Chats/Index', [
            'conversations' => $this->conversationsListFor($user),
            'users'         => $this->pickerUsersFor($user),
            'active'        => $active ? $this->threadPayloadFor($active) : null,
        ]);
    }


    private function conversationsListFor(CraftableProUser $user)
    {
        return Conversation::forUser($user)
            ->with([
                'latestMessage',
                'members:id,first_name,last_name',
            ])
            ->latest('updated_at')
            ->get()
            ->map(fn (Conversation $c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'type'         => $c->type,
                'updated_at'   => $c->updated_at?->toIso8601String(),
                'last_message' => $c->latestMessage?->only(['id', 'body', 'created_at']),
                'members'      => $c->members->map(fn ($m) => [
                    'id'         => $m->id,
                    'first_name' => $m->first_name,
                    'last_name'  => $m->last_name,
                ])->values(),
            ])
            ->values();
    }

    private function pickerUsersFor(CraftableProUser $user)
    {
        $allowed = $this->allowedTargetRolesFor($user);

        return CraftableProUser::query()
            ->where('id', '!=', $user->id)
            ->when($allowed !== ['*'], fn ($q) => $q->role($allowed))
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();
    }


    private function threadPayloadFor(Conversation $conversation): array
    {
        return [
            'id'       => $conversation->id,
            'name'     => $conversation->name,
            'type'     => $conversation->type,
            'members'  => $conversation->members()
                ->select('craftable_pro_users.id', 'first_name', 'last_name')
                ->get(),
            'messages' => $conversation->messages()
                ->with('sender:id,first_name,last_name')
                ->oldest()
                ->get()
                ->map(fn (Message $m) => [
                    'id'         => $m->id,
                    'body'       => $m->body,
                    'user_id'    => $m->user_id,
                    'created_at' => $m->created_at?->toIso8601String(),
                    'sender'     => $m->sender ? [
                        'id'         => $m->sender->id,
                        'first_name' => $m->sender->first_name,
                        'last_name'  => $m->sender->last_name,
                    ] : null,
                ]),
        ];
    }


    private function markAsRead(Conversation $conversation): void
    {
        $conversation->members()->updateExistingPivot(
            auth('craftable-pro')->id(),
            ['last_read_at' => now()],
        );
    }


    public function leave(Conversation $conversation): RedirectResponse
    {
        $this->ensureMember($conversation);

        return redirect()->route('chats.index');
    }


    public function support(): \Illuminate\Http\JsonResponse
    {
        $user = auth('craftable-pro')->user();
        abort_unless($user->roles->pluck('name')->contains('client'), 403);

        $conversation = Conversation::supportFor($user);

        if (! $conversation) {
            return response()->json(['conversation_id' => null, 'support_user' => null, 'messages' => []]);
        }

        $this->markAsRead($conversation);

        $supportUser = $conversation->members()
            ->where('craftable_pro_users.id', '!=', $user->id)
            ->select('craftable_pro_users.id', 'first_name', 'last_name')
            ->first();

        return response()->json([
            'conversation_id' => $conversation->id,
            'support_user'    => $supportUser?->only(['id', 'first_name', 'last_name']),
            'current_user_id' => $user->id,
            'messages'        => $conversation->messages()
                ->with('sender:id,first_name,last_name')
                ->oldest()
                ->get()
                ->map(fn (Message $m) => [
                    'id'         => $m->id,
                    'body'       => $m->body,
                    'user_id'    => $m->user_id,
                    'created_at' => $m->created_at?->toIso8601String(),
                ]),
        ]);
    }

    private function ensureMember(Conversation $conversation): void
    {
        $userId = auth('craftable-pro')->id();
        abort_unless(
            $conversation->members()->where('craftable_pro_users.id', $userId)->exists(),
            403,
        );
    }

    
    private function allowedTargetRolesFor(CraftableProUser $user): array
    {
        if ($user->can('craftable-pro.chat.message-everyone')) {
            return ['*'];
        }

        return Role::query()
            ->where('guard_name', 'craftable-pro')
            ->pluck('name')
            ->filter(fn (string $role) => $user->can("craftable-pro.chat.message-{$role}"))
            ->values()
            ->all();
    }
}

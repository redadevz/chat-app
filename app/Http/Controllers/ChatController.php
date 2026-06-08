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
    
    private function user(): CraftableProUser
    {
        return auth('craftable-pro')->user();
    }

    private function userId(): int
    {
        return auth('craftable-pro')->id();
    }

    public function index(): Response
    {
        $user = $this->user();

        abort_if($this->isClient($user), 403);

        return $this->render();
    }

    public function show(Conversation $conversation): Response
    {
        $user = $this->user();

        abort_if($this->isClient($user), 403);

        if ($this->isOversight($user) && ! $this->isMember($conversation, $user->id)) {
            $conversation->members()->attach($user->id, ['joined_at' => now()]);
        }

        $this->ensureMember($conversation);
        $this->markAsRead($conversation);

        return $this->render($conversation);
    }

    public function store(StoreChatRequest $request): RedirectResponse
    {
        $user  = $this->user();
        $other = $request->validated('user_id');

        $conversation = Conversation::findOrCreatePrivateBetween($user, $other);

        return redirect()->route('chats.show', $conversation);
    }

    public function storeMessage(StoreMessageRequest $request, Conversation $conversation): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $user = $this->user();

        $visibility = $request->validated('visibility', config('chat.visibility.default'));
        if ($visibility === config('chat.visibility.internal') && ! $this->isStaff($user)) {
            $visibility = config('chat.visibility.public');
        }

        $message = $conversation->messages()->create([
            'user_id'       => $user->id,
            'body'          => $request->validated('body'),
            'type'          => config('chat.messages.default_type'),
            'visibility'    => $visibility,
            'reply_to_id'   => $request->validated('reply_to_id'),
            'private_to_id' => $request->validated('private_to_id'),
        ]);

        $conversation->touch();

        broadcast(new MessageSent($message->load('sender', 'replyTo.sender', 'recipient')))->toOthers();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => [
                    'id'          => $message->id,
                    'body'        => $message->body,
                    'user_id'     => $message->user_id,
                    'visibility'  => $message->visibility,
                    'reply_to_id' => $message->reply_to_id,
                    'private_to_id' => $message->private_to_id,
                    'created_at'  => $message->created_at?->toIso8601String(),
                    'conversation_id' => $message->conversation_id,
                ],
            ]);
        }

        return redirect()->route('chats.show', $conversation);
    }


    private function render(?Conversation $active = null): Response
    {
        $user = $this->user();

        return Inertia::render('Chats/Index', [
            'conversations'  => $this->conversationsListFor($user),
            'users'          => $this->pickerUsersFor($user),
            'active'         => $active ? $this->threadPayloadFor($active, $user->id) : null,
            'oversightRoles' => config('chat.roles.oversight'),
        ]);
    }


    private function conversationsListFor(CraftableProUser $user)
    {

        $query = $this->isOversight($user)
            ? Conversation::query()
            : Conversation::forUser($user);

        return $query
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


    private function threadPayloadFor(Conversation $conversation, int $viewerId): array
    {
        return [
            'id'       => $conversation->id,
            'name'     => $conversation->name,
            'type'     => $conversation->type,
            'members'  => $conversation->members()
                ->select('craftable_pro_users.id', 'first_name', 'last_name')
                ->get(),
            'messages' => $conversation->messages()
                ->visibleTo($viewerId)
                ->with([
                    'sender:id,first_name,last_name',
                    'replyTo:id,body,user_id',
                    'replyTo.sender:id,first_name,last_name',
                    'recipient:id,first_name,last_name',
                ])
                ->oldest()
                ->get()
                ->map(fn (Message $m) => [
                    'id'          => $m->id,
                    'body'        => $m->body,
                    'user_id'     => $m->user_id,
                    'visibility'  => $m->visibility,
                    'reply_to_id' => $m->reply_to_id,
                    'reply_to'    => $this->replyToPayload($m),
                    'private_to_id' => $m->private_to_id,
                    'recipient'   => $m->recipient ? [
                        'id'         => $m->recipient->id,
                        'first_name' => $m->recipient->first_name,
                        'last_name'  => $m->recipient->last_name,
                    ] : null,
                    'created_at'  => $m->created_at?->toIso8601String(),
                    'sender'      => $m->sender ? [
                        'id'         => $m->sender->id,
                        'first_name' => $m->sender->first_name,
                        'last_name'  => $m->sender->last_name,
                    ] : null,
                ]),
        ];
    }

    private function replyToPayload(Message $message): ?array
    {
        $parent = $message->replyTo;

        if (! $parent) {
            return null;
        }

        return [
            'id'     => $parent->id,
            'body'   => $parent->body,
            'sender' => $parent->sender ? [
                'first_name' => $parent->sender->first_name,
                'last_name'  => $parent->sender->last_name,
            ] : null,
        ];
    }


    private function markAsRead(Conversation $conversation): void
    {
        $conversation->members()->updateExistingPivot(
            $this->userId(),
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
        $user = $this->user();
        abort_unless($this->isClient($user), 403);

        $conversation = Conversation::supportFor($user);

        if (! $conversation) {
            return response()->json(['conversation_id' => null, 'support_user' => null, 'messages' => []]);
        }

        $this->markAsRead($conversation);

        // The client must only ever see their account manager as the single
        // point of contact — never a super-admin who joined to oversee.
        $supportUser = $conversation->members()
            ->role(config('chat.roles.account_manager'))
            ->where('craftable_pro_users.id', '!=', $user->id)
            ->select('craftable_pro_users.id', 'first_name', 'last_name')
            ->first();

        return response()->json([
            'conversation_id' => $conversation->id,
            'support_user'    => $supportUser?->only(['id', 'first_name', 'last_name']),
            'current_user_id' => $user->id,
            'messages'        => $conversation->messages()
                ->public()
                ->visibleTo($user->id)
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
        $userId = $this->userId();
        abort_unless($this->isMember($conversation, $userId), 403);
    }

    private function isMember(Conversation $conversation, int $userId): bool
    {
        return $conversation->members()->where('craftable_pro_users.id', $userId)->exists();
    }

    private function isClient(CraftableProUser $user): bool
    {
        return $user->roles->pluck('name')->contains(config('chat.roles.client'));
    }

    /** Oversight = roles that see every conversation and auto-join on open. */
    private function isOversight(CraftableProUser $user): bool
    {
        return $user->roles->pluck('name')
            ->intersect(config('chat.roles.oversight'))
            ->isNotEmpty();
    }

    /** Staff = the roles allowed to post and read internal notes. */
    private function isStaff(CraftableProUser $user): bool
    {
        return $user->roles->pluck('name')
            ->intersect(config('chat.roles.staff'))
            ->isNotEmpty();
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

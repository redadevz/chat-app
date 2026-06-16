<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\ConversationRead;
use App\Events\MessageSent;
use App\Http\Requests\CraftablePro\Chat\IndexChatRequest;
use App\Http\Requests\CraftablePro\Chat\LeaveChatRequest;
use App\Http\Requests\CraftablePro\Chat\ShowChatRequest;
use App\Http\Requests\CraftablePro\Chat\StoreChatRequest;
use App\Http\Requests\CraftablePro\Chat\SupportChatRequest;
use App\Http\Requests\CraftablePro\Message\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Settings\ChatSettings;
use Brackets\CraftablePro\Models\CraftableProUser;
use Brackets\CraftablePro\Queries\Filters\FuzzyFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ChatController extends Controller
{
    private function user(): CraftableProUser
    {
        return auth('craftable-pro')->user();
    }

    private function userId(): int
    {
        return (int) $this->user()->id;
    }

    private function settings(): ChatSettings
    {
        return app(ChatSettings::class);
    }

    public function index(IndexChatRequest $request): Response
    {
        return $this->render();
    }

    public function show(ShowChatRequest $request, Conversation $conversation): Response
    {
        $conversation->load('members.roles');

        $this->markAsRead($conversation);

        return $this->render($conversation);
    }

    public function store(StoreChatRequest $request): RedirectResponse
    {
        $conversation = Conversation::findOrCreatePrivateBetween(
            $this->user(),
            $request->validated('user_id'),
        );

        return redirect()->route('chats.show', $conversation);
    }

    public function storeMessage(StoreMessageRequest $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $message = $conversation->postMessage($this->user(), $request->validated());

        broadcast(new MessageSent(
            $message->load('sender', 'replyTo.sender', 'recipient'),
            $conversation->recipientIdsFor($message),
        ))->toOthers();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => [
                    'id'              => $message->id,
                    'body'            => $message->body,
                    'user_id'         => $message->user_id,
                    'visibility'      => $message->visibility,
                    'reply_to_id'     => $message->reply_to_id,
                    'private_to_id'   => $message->private_to_id,
                    'created_at'      => $message->created_at,
                    'conversation_id' => $message->conversation_id,
                ],
            ]);
        }

        return redirect()->route('chats.show', $conversation);
    }

    public function leave(LeaveChatRequest $request, Conversation $conversation): RedirectResponse
    {
        $conversation->removeMember($this->userId());

        return redirect()->route('chats.index');
    }

    public function support(SupportChatRequest $request): JsonResponse
    {
        $user         = $this->user();
        $conversation = Conversation::supportFor($user);

        if (! $conversation) {
            return response()->json(['conversation_id' => null, 'support_user' => null, 'messages' => []]);
        }

        $this->markAsRead($conversation);

        $supportUser = $conversation->members()
            ->role($this->settings()->roles['account_manager'])
            ->where('craftable_pro_users.id', '!=', $user->id)
            ->first();

        return response()->json([
            'conversation_id' => $conversation->id,
            'support_user'    => $supportUser ? [
                'id'           => $supportUser->id,
                'first_name'   => $supportUser->first_name,
                'last_name'    => $supportUser->last_name,
                'last_read_at' => $supportUser->pivot->last_read_at
                    ? Carbon::parse($supportUser->pivot->last_read_at)->toIso8601String()
                    : null,
            ] : null,
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

    private function render(?Conversation $active = null): Response
    {
        $user = $this->user();

        return Inertia::render('Chats/Index', [
            'conversations'  => $this->conversationsListFor($user),
            'users'          => $this->pickerUsersFor($user),
            'active'         => $active ? $this->threadPayloadFor($active, $user->id) : null,
            'oversightRoles' => Role::whereIn('id', $this->settings()->roles['oversight'])->pluck('name'),
        ]);
    }

    private function conversationsListFor(CraftableProUser $user)
    {
        return QueryBuilder::for(Conversation::visibleTo($user))
            ->allowedFilters([
                AllowedFilter::custom('search', new FuzzyFilter('name', 'type')),
            ])
            ->defaultSort('-updated_at')
            ->allowedSorts(['name', 'type', 'updated_at'])
            ->with([
                'latestMessage',
                'members:id,first_name,last_name',
            ])
            ->get()
            ->map(fn (Conversation $c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'type'         => $c->type,
                'updated_at'   => $c->updated_at?->toIso8601String(),
                'last_message' => $c->latestMessage?->only(['id', 'body', 'created_at']),
                'members'      => $c->members->map(fn (CraftableProUser $m) => [
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

        $base = CraftableProUser::query()
            ->where('id', '!=', $user->id)
            ->when($allowed !== ['*'], fn ($q) => $q->role($allowed))
            ->select('id', 'first_name', 'last_name');

        $request = new Request(['filter' => ['search' => request('user_search')]]);

        return QueryBuilder::for($base, $request)
            ->allowedFilters([
                AllowedFilter::custom('search', new FuzzyFilter('first_name', 'last_name')),
            ])
            ->defaultSort('first_name')
            ->allowedSorts(['first_name', 'last_name'])
            ->get()
            ->map(fn (CraftableProUser $u) => [
                'id'         => $u->id,
                'first_name' => $u->first_name,
                'last_name'  => $u->last_name,
            ])
            ->values();
    }

    private function threadPayloadFor(Conversation $conversation, int $user_id): array
    {
        $staffRoles = $this->settings()->roles['staff'];

        return [
            'id'       => $conversation->id,
            'name'     => $conversation->name,
            'type'     => $conversation->type,
            'members'  => $conversation->members->map(fn (CraftableProUser $m) => [
                ...$m->only(['id', 'first_name', 'last_name']),
                'is_staff'     => $m->hasAnyRole($staffRoles),
                'last_read_at' => $m->pivot->last_read_at,
            ])->values(),
            'messages' => $conversation->messages()
                ->visibleTo($user_id)
                ->with([
                    'sender:id,first_name,last_name',
                    'replyTo:id,body,user_id',
                    'replyTo.sender:id,first_name,last_name',
                    'recipient:id,first_name,last_name',
                ])
                ->oldest()
                ->get()
                ->map(fn (Message $m) => [
                    'id'            => $m->id,
                    'body'          => $m->body,
                    'user_id'       => $m->user_id,
                    'visibility'    => $m->visibility,
                    'reply_to_id'   => $m->reply_to_id,
                    'reply_to'      => $m->replyTo ? [
                        ...$m->replyTo->only(['id', 'body']),
                        'sender' => $m->replyTo->sender?->only(['id', 'first_name', 'last_name']),
                    ] : null,
                    'private_to_id' => $m->private_to_id,
                    'recipient'     => $m->recipient?->only(['id', 'first_name', 'last_name']),
                    'created_at'    => $m->created_at?->toIso8601String(),
                    'sender'        => $m->sender?->only(['id', 'first_name', 'last_name']),
                ]),
        ];
    }

    private function markAsRead(Conversation $conversation): void
    {
        $userId = $this->userId();
        $readAt = $conversation->markReadBy($userId);

        if (! $readAt) {
            return;
        }

        $recipients = $conversation->audienceIds()
            ->reject(fn (int $id) => $id === $userId)
            ->values()
            ->all();

        broadcast(new ConversationRead(
            $conversation->id,
            $userId,
            $readAt->toIso8601String(),
            $recipients,
        ))->toOthers();
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

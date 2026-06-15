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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ChatController extends Controller
{
    private function user(): CraftableProUser
    {
        return auth('craftable-pro')->user();
    }

    private function settings(): ChatSettings
    {
        return app(ChatSettings::class);
    }

    private function isStaff(CraftableProUser $user): bool
    {
        return $user->hasAnyRole($this->settings()->roles['staff']);
    }

    private function isOversight(CraftableProUser $user): bool
    {
        return $user->hasAnyRole($this->settings()->roles['oversight']);
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

    public function storeMessage(StoreMessageRequest $request, Conversation $conversation, ChatSettings $settings): RedirectResponse|JsonResponse
    {
        $user = $this->user();

        $visibility = $request->validated('visibility', $settings->default_visibility);
        if ($visibility === $settings->visibility['internal'] && ! $this->isStaff($user)) {
            $visibility = $settings->visibility['public'];
        }

        $message = $conversation->messages()->create([
            'user_id'       => $user->id,
            'body'          => $request->validated('body'),
            'type'          => $settings->message_default_type,
            'visibility'    => $visibility,
            'reply_to_id'   => $request->validated('reply_to_id'),
            'private_to_id' => $settings->whispers_enabled ? $request->validated('private_to_id') : null,
        ]);

        $conversation->touch();

        broadcast(new MessageSent(
            $message->load('sender', 'replyTo.sender', 'recipient'),
            $this->messageRecipientIds($conversation, $message),
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
                ...$this->basicUser($supportUser),
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
            'oversightRoles' => $this->settings()->roles['oversight'],
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
            ->map(fn (Conversation $c) => $this->conversationSummary($c))
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
            ->get()
            ->map(fn (CraftableProUser $u) => $this->basicUser($u))
            ->values();
    }

    private function threadPayloadFor(Conversation $conversation, int $viewerId): array
    {
        return [
            'id'       => $conversation->id,
            'name'     => $conversation->name,
            'type'     => $conversation->type,
            'members'  => $conversation->members
                ->map(fn (CraftableProUser $m) => $this->memberPayload($m))
                ->values(),
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
                ->map(fn (Message $m) => $this->messagePayload($m)),
        ];
    }

    /** A conversation as it appears in the sidebar list. */
    private function conversationSummary(Conversation $conversation): array
    {
        return [
            'id'           => $conversation->id,
            'name'         => $conversation->name,
            'type'         => $conversation->type,
            'updated_at'   => $conversation->updated_at?->toIso8601String(),
            'last_message' => $conversation->latestMessage?->only(['id', 'body', 'created_at']),
            'members'      => $conversation->members
                ->map(fn (CraftableProUser $m) => $this->basicUser($m))
                ->values(),
        ];
    }

    /** A member inside an open thread: basic identity plus chat-specific flags. */
    private function memberPayload(CraftableProUser $member): array
    {
        return [
            ...$this->basicUser($member),
            'is_staff'     => $this->isStaff($member),
            'last_read_at' => $member->pivot->last_read_at,
        ];
    }

    /** A single message as the thread view renders it. */
    private function messagePayload(Message $message): array
    {
        return [
            'id'            => $message->id,
            'body'          => $message->body,
            'user_id'       => $message->user_id,
            'visibility'    => $message->visibility,
            'reply_to_id'   => $message->reply_to_id,
            'reply_to'      => $this->replyToPayload($message),
            'private_to_id' => $message->private_to_id,
            'recipient'     => $this->basicUser($message->recipient),
            'created_at'    => $message->created_at?->toIso8601String(),
            'sender'        => $this->basicUser($message->sender),
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
            'sender' => $this->basicUser($parent->sender),
        ];
    }

    private function markAsRead(Conversation $conversation): void
    {
        $userId  = $this->user()->id;
        $readAt  = now();
        $updated = $conversation->members()->updateExistingPivot(
            $userId,
            ['last_read_at' => $readAt],
        );

        // Only a real member's read counts as a receipt — tell the others live.
        if ($updated) {
            $recipients = $this->audienceIds($conversation)
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
    }

    /** Everyone who may see this conversation live: its members plus oversight users. */
    private function audienceIds(Conversation $conversation): \Illuminate\Support\Collection
    {
        return $conversation->members
            ->pluck('id')
            ->merge(CraftableProUser::role($this->settings()->roles['oversight'])->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Whose personal channel receives a freshly sent message. We pick the exact
     * recipients so an unauthorized socket never receives it: a whisper reaches
     * only its two parties, an internal note only the staff in the audience.
     *
     * @return array<int>
     */
    private function messageRecipientIds(Conversation $conversation, Message $message): array
    {
        if ($message->private_to_id !== null) {
            return array_values(array_unique([
                (int) $message->user_id,
                (int) $message->private_to_id,
            ]));
        }

        $audience = $this->audienceIds($conversation);

        if ($message->isInternal()) {
            $audience = CraftableProUser::role($this->settings()->roles['staff'])
                ->whereIn('id', $audience)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        return $audience->all();
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

    /**
     * The minimal user shape the chat UI needs everywhere. Returning plain arrays
     * (rather than the model) keeps the appended avatar/media attributes from
     * triggering an N+1 of media queries during serialization.
     *
     * @return ($user is null ? null : array{id: int, first_name: string, last_name: string})
     */
    private function basicUser(?CraftableProUser $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Events;

use App\Settings\ChatSettings;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class ConversationRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $recipientIds  the other members/oversight users to notify
     */
    public function __construct(
        public int $conversationId,
        public int $userId,
        public string $readAt,
        public array $recipientIds,
    ) {
    }

    public function broadcastOn(): array
    {
        $userPrefix = app(ChatSettings::class)->channels['user_prefix'];

        return array_map(
            fn (int $id) => new PrivateChannel("{$userPrefix}.{$id}"),
            $this->recipientIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'conversation.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id'         => $this->userId,
            'last_read_at'    => $this->readAt,
        ];
    }
}

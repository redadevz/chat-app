<?php

declare(strict_types=1);

namespace App\Events;

use App\Settings\ChatSettings;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a member reads a conversation, so other members' "Seen" receipts
 * on their own messages update live.
 */
class ConversationRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $userId,
        public string $readAt,
    ) {
    }

    public function broadcastOn(): array
    {
        $prefix = app(ChatSettings::class)->channels['prefix'];

        return [new PrivateChannel("{$prefix}.{$this->conversationId}")];
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

<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
    }

    /**
     * Private channel scoped per conversation. Listeners must be members
     * (see routes/channels.php).
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->message->conversation_id}"),
        ];
    }

    /**
     * Shorter, stable event name on the client. Without this, Echo expects
     * the FQCN ("App\\Events\\MessageSent").
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * The payload sent to subscribers. Keep it flat — same shape as the REST
     * API response so the Vue list can append without transformation.
     */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'body'       => $this->message->body,
            'user_id'    => $this->message->user_id,
            'created_at' => $this->message->created_at?->toIso8601String(),
            'conversation_id' => $this->message->conversation_id,
            'sender'     => $this->message->sender ? [
                'id'         => $this->message->sender->id,
                'first_name' => $this->message->sender->first_name,
                'last_name'  => $this->message->sender->last_name,
            ] : null,
        ];
    }
}

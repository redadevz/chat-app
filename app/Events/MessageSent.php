<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
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
        // Internal (staff-only) notes ride a separate channel that clients are
        // not allowed to subscribe to, so they can never receive them.
        $prefix = config('chat.channels.prefix');
        $suffix = $this->message->isInternal() ? config('chat.channels.internal_suffix') : '';

        return [
            new PrivateChannel("{$prefix}.{$this->message->conversation_id}{$suffix}"),
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
            'id'          => $this->message->id,
            'body'        => $this->message->body,
            'user_id'     => $this->message->user_id,
            'visibility'  => $this->message->visibility,
            'reply_to_id' => $this->message->reply_to_id,
            'reply_to'    => $this->replyToPayload(),
            'created_at'  => $this->message->created_at?->toIso8601String(),
            'conversation_id' => $this->message->conversation_id,
            'sender'      => $this->message->sender ? [
                'id'         => $this->message->sender->id,
                'first_name' => $this->message->sender->first_name,
                'last_name'  => $this->message->sender->last_name,
            ] : null,
        ];
    }

    /** A compact snapshot of the message this one replies to, for inline quoting. */
    private function replyToPayload(): ?array
    {
        $parent = $this->message->replyTo;

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
}

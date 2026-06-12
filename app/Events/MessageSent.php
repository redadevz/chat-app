<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use App\Settings\ChatSettings;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $recipientIds  users whose personal channel should receive this
     */
    public function __construct(
        public Message $message,
        public array $recipientIds,
    ) {
    }

    /**
     * Every user listens on one personal channel; we deliver the message to the
     * exact set of recipients computed by the controller. Because we choose who
     * to broadcast to, an unauthorized user's socket never receives it at all —
     * whispers reach only their two parties, internal notes only staff.
     */
    public function broadcastOn(): array
    {
        $userPrefix = app(ChatSettings::class)->channels['user_prefix'];

        return array_map(
            fn (int $id) => new PrivateChannel("{$userPrefix}.{$id}"),
            $this->recipientIds,
        );
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
            'private_to_id' => $this->message->private_to_id,
            'recipient'   => $this->message->recipient ? [
                'id'         => $this->message->recipient->id,
                'first_name' => $this->message->recipient->first_name,
                'last_name'  => $this->message->recipient->last_name,
            ] : null,
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

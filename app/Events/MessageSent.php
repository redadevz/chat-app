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


    public function broadcastOn(): array
    {
        return array_map(
            fn (int $id) => new PrivateChannel("user.{$id}"),
            $this->recipientIds,
        );
    }


    public function broadcastAs(): string
    {
        return 'message.sent';
    }


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

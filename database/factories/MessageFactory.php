<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ReplyTo;
use App\Models\User;

class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'reply_to_id' => ReplyTo::factory(),
            'body' => $this->faker->text(),
            'type' => $this->faker->word(),
        ];
    }
}

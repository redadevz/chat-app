<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\CreatedBy;

class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'created_by' => CreatedBy::factory(),
            'name' => $this->faker->name(),
            'type' => $this->faker->word(),
        ];
    }
}

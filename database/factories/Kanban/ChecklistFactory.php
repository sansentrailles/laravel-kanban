<?php

namespace Database\Factories\Kanban;

use App\Models\Kanban\Card;
use App\Models\Kanban\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checklist>
 */
class ChecklistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'title' => fake()->words(2, true),
            'ord' => fake()->numberBetween(1, 100),
        ];
    }
}

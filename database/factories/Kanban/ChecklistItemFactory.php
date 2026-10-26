<?php

namespace Database\Factories\Kanban;

use App\Models\Kanban\Checklist;
use App\Models\Kanban\ChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checklist_id' => Checklist::factory(),
            'content' => fake()->words(2, true),
            'ord' => fake()->numberBetween(1, 100),
        ];
    }
}

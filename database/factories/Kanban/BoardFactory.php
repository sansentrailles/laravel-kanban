<?php

namespace Database\Factories\Kanban;

use App\Models\Kanban\Board;
use App\Models\Kanban\Workspace;
use App\Models\User;
use App\Providers\Enums\Kanban\BoardVisibility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    protected $model = Board::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'created_by' => User::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['🚀', '🎨', '💻', '📊', '']),
            'order' => fake()->numberBetween(1, 100),
            'visibility' => fake()->randomElement(BoardVisibility::cases()),
            'settings' => [
                'hide_completed_cards' => false,
                'enable_wip_limits' => true,
                'default_view' => 'board',
            ],
        ];
    }
}

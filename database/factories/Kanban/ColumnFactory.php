<?php

namespace Database\Factories\Kanban;

use App\Models\Kanban\Board;
use App\Models\Kanban\Column;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Column>
 */
class ColumnFactory extends Factory
{
    protected $model = Column::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'title' => fake()->randomElement(['Backlog', 'To Do', 'In Progress', 'Review', 'Done']),
            'color' => fake()->optional()->hexColor(),
            'order' => fake()->numberBetween(1, 100),
            'is_hidden' => false,
            'wip_limit' => fake()->optional(0.3)->randomElement([3, 5, 10, null]),
            'settings' => [
                'require_assignee' => true,
                'auto_archive_days' => null,
            ],
        ];
    }

    /**
     * Состояние: скрытая колонка
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }

    /**
     * Состояние: колонка с жестким WIP лимитом
     */
    public function withWipLimit(int $limit = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'wip_limit' => $limit,
        ]);
    }
}

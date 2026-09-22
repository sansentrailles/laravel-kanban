<?php

namespace Database\Factories\Kanban;

use App\Models\Kanban\Label;
use App\Models\Kanban\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Label>
 */
class LabelFactory extends Factory
{
    protected $model = Label::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement([
                'Bug', 'Feature', 'Design', 'Backend', 'Frontend',
                'DevOps', 'Documentation', 'Urgent', 'Good First Issue',
            ]),
            'color' => fake()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Состояние: метка с конкретным цветом
     */
    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }
}

<?php

namespace Database\Factories\Kanban;

use App\Models\Kanban\Card;
use App\Models\Kanban\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'is_edited' => false,
        ];
    }

    /**
     * Отредактированный комментарий.
     */
    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_edited' => true,
        ]);
    }
}

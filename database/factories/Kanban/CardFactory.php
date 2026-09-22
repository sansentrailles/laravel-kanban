<?php

namespace Database\Factories\Kanban;

use App\Enums\Kanban\CardPriority;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'column_id' => Column::factory(),
            'created_by' => User::factory(),

            // Реалистичные заголовки задач
            'title' => fake()->sentence(fake()->numberBetween(3, 8)),
            'description' => fake()->optional(0.7)->paragraph(2, true),

            // Генерация дробного индекса для сортировки (имитация Fractional Indexing)
            // Генерирация числа в диапазоне 1000000000 - 2000000000 с 10 знаками после запятой
            'order' => fake()->randomFloat(10, 1000000000, 2000000000),

            'priority' => fake()->randomElement(CardPriority::cases()),

            // Даты: 60% крточек имеет дедлайн
            'due_date' => fake()->boolean(60)
                ? fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d')
                : null,

            // Даты: 40% карточек имеют дату начала (в прошлом или настоящем)
            'start_date' => fake()->boolean(40)
                ? fake()->dateTimeBetween('-2 weeks', 'now')->format('Y-m-d')
                : null,

            // 30% карточек выполнены
            'completed_at' => fake()->boolean(30)
                ? fake()->dateTime()
                : null,

            // Гибкие настройки
            'settings' => [
                'hide_from_board' => false,
                'require_assignee' => fake()->boolean(20), // 20% карточек требуют исполнителя
            ],
        ];
    }

    // ─────────────────────────────────────────────
    //  States (Состояния для специфичных сценариев)
    // ─────────────────────────────────────────────

    /**
     * Карточка с просроченным дедлайном (для тестирования UI-индикаторов).
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-1 month', '-1 day')->format('Y-m-d'),
            'completed_at' => null,
        ]);
    }

    /**
     * Завершенная карточка
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'due_date' => fake()->optional()?->dateTimeBetween('-2 weeks', '-1 day')?->format('Y-m-d'),
        ]);
    }

    /**
     * Карточка с высоким или срочным приоритетом
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->randomElement([CardPriority::High, CardPriority::Urgent]),
        ]);
    }

    /**
     * Карточка без описания
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    /**
     * Карточка создания конкретного пользователя
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}

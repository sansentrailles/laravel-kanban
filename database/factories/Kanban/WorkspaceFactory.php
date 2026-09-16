<?php

namespace Database\Factories\Kanban;

use App\Models\Kanban\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).' - '.fake()->unique()->randomNumber(5),
            'description' => fake()->optional()->paragraph(),
            'owner_id' => User::factory(),
            'settings' => [
                'default_view' => 'board',
                'allow_guests' => false,
            ],
        ];
    }
}

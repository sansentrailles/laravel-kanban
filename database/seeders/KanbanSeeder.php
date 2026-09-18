<?php

namespace Database\Seeders;

use App\Models\Kanban\Board;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\Kanban\Label;
use App\Models\Kanban\Workspace;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KanbanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем пользователя или берем первого
        $user = User::first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => bcrypt('123123123'),
        ]);

        // Создание воркспейса
        $workspace = Workspace::factory()->create([
            'name' => 'Project Sandbox',
            'owner_id' => $user->id,
        ]);

        // Привязка пользователя воркспейса
        $workspace->members()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Создание меток
        $labels = Label::factory(5)->create(['workspace_id' => $workspace->id]);

        // Создание доски
        $board = Board::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'name' => "Main Board",
        ]);

        // Создание колонок
        $columns = Column::factory(4)->create(['board_id' => $board->id]);

        // Создание карточек в каждой колонке
        foreach ($columns as $column) {
            $cards = Card::factory(3)->create(['column_id' => $column->id]);

            foreach ($cards as $card) {
                $card->lables()->attach(
                    $labels->random(rand(1, 3))->pluck('id')
                );

                $card->assignee()->attach($user->id);
            }
        }
    }
}

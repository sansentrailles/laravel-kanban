<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Kanban\Board;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\Kanban\Label;
use App\Models\Kanban\Workspace;
use App\Models\User;
use Illuminate\Database\Seeder;

final class KanbanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=KanbanSeeder
     */
    public function run(): void
    {
        $password = bcrypt('123123123');

        // Создаем трех пользователей
        $user1 = User::firstOrCreate(
            ['email' => 'dev@gmail.com'],
            ['name' => 'Dev Lead', 'password' => $password, 'email_verified_at'=> now()]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            ['name' => 'Alice Manager', 'password' => $password, 'email_verified_at'=> now()]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            ['name' => 'Bob Designer', 'password' => $password, 'email_verified_at'=> now()]
        );

        // Конфигурация воркспейсов и распределение участников
        $workspacesConfig = [
            [
                'name' => 'Project Sandbox',
                'owner' => $user1,
                'members' => [$user1, $user2, $user3], // 3 члена (все пользователи)
                'board_name' => 'Main Development Board',
                'board_icon' => '🚀',
                'board_color' => '#3b82f6',
            ],
            [
                'name' => 'Marketing Q4',
                'owner' => $user2,
                'members' => [$user2], // 1 член (только владелец)
                'board_name' => 'Campaigns & Content',
                'board_icon' => '📈',
                'board_color' => '#10b981',
            ],
            [
                'name' => 'HR & Onboarding',
                'owner' => $user3,
                'members' => [$user3, $user1], // 2 члена (владелец + Dev Lead)
                'board_name' => 'New Hires Pipeline',
                'board_icon' => '👥',
                'board_color' => '#a855f7',
            ],
        ];

        // Создаем каждый воркспейс
        foreach ($workspacesConfig as $config) {
            $this->createPopulatedWorkspace($config['owner'], $config['members'], $config);
        }
    }

    /**
     * Создает один воркспейс со всеми связанными сущностями.
     */
    private function createPopulatedWorkspace(User $owner, array $members, array $config): void
    {
        // Инициализируем модель без сохранения в БД, чтобы получить доступ к её методам
        $workspaceInstance = Workspace::factory()->make();

        $slug = $workspaceInstance->generateUniqueSlug($config['name']);

        // Создание воркспейса
        $workspace = Workspace::factory()->create([
            'name' => $config['name'],
            'owner_id' => $owner->id,
            'slug' => $slug,
        ]);

        // Привязка всех участников с правильными ролями
        foreach ($members as $member) {
            $role = $member->id === $owner->id ? 'owner' : 'member';
            $workspace->members()->attach($member->id, [
                'role' => $role,
                'joined_at' => now(),
            ]);
        }

        // Создание меток
        $labelsData = [
            ['name' => 'Bug', 'color' => '#ef4444', 'description' => 'Ошибка или дефект в коде'],
            ['name' => 'Feature', 'color' => '#3b82f6', 'description' => 'Новая функциональность'],
            ['name' => 'Design', 'color' => '#a855f7', 'description' => 'Задачи по UI/UX или макетам'],
            ['name' => 'Backend', 'color' => '#10b981', 'description' => 'Серверная разработка'],
            ['name' => 'Urgent', 'color' => '#f59e0b', 'description' => 'Требует немедленного внимания'],
        ];

        $labels = collect($labelsData)->map(fn ($data) => Label::factory()->create(array_merge($data, [
            'workspace_id' => $workspace->id,
        ]))
        );

        // Создание доски
        $board = Board::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'name' => $config['board_name'],
            'icon' => $config['board_icon'],
            'color' => $config['board_color'],
        ]);

        // Создание реалистичных колонок
        $columnsData = [
            ['title' => 'Backlog', 'order' => 100],
            ['title' => 'To Do', 'order' => 200],
            ['title' => 'In Progress', 'order' => 300, 'wip_limit' => 5],
            ['title' => 'Review', 'order' => 400],
            ['title' => 'Done', 'order' => 500],
        ];

        $columns = collect($columnsData)->map(fn ($data) => Column::factory()->create(array_merge($data, [
            'board_id' => $board->id,
        ]))
        )->values();

        // Вспомогательная функция для получения случайного участника этого воркспейса
        $getRandomMember = fn () => collect($members)->random();

        // Создание карточек с использованием States

        // Backlog: Обычные задачи с 1-2 случайными метками
        Card::factory(4)->create([
            'column_id' => $columns[0]->id,
            'created_by' => $getRandomMember()->id]
        )
            ->each(fn ($card) => $card->labels()->attach($labels->random(fake()->numberBetween(1, 2))->pluck('id')));

        // To Do: Задачи без описания + метка Feature
        Card::factory(2)->withoutDescription()->create(['column_id' => $columns[1]->id, 'created_by' => $getRandomMember()->id])
            ->each(function ($card) use ($labels) {
                $featureLabel = $labels->firstWhere('name', 'Feature');
                if ($featureLabel) {
                    $card->labels()->attach($featureLabel->id);
                }
            });

        // In Progress: Срочные задачи с назначенным исполнителем и метками Urgent/Backend
        Card::factory(3)->urgent()->create(['column_id' => $columns[2]->id, 'created_by' => $getRandomMember()->id])
            ->each(function ($card) use ($labels, $getRandomMember) {
                $urgentLabel = $labels->firstWhere('name', 'Urgent');
                $backendLabel = $labels->firstWhere('name', 'Backend');
                $card->labels()->attach(collect([$urgentLabel, $backendLabel])->filter()->pluck('id'));
                $card->assignees()->attach($getRandomMember()->id);
            });

        // Review: Просроченная задача + метка Bug
        Card::factory(1)->overdue()->create(['column_id' => $columns[3]->id, 'created_by' => $getRandomMember()->id])
            ->each(function ($card) use ($labels, $getRandomMember) {
                $bugLabel = $labels->firstWhere('name', 'Bug');
                if ($bugLabel) {
                    $card->labels()->attach($bugLabel->id);
                }
                $card->assignees()->attach($getRandomMember()->id);
            });

        // Done: Завершенные задачи с меткой Design
        Card::factory(3)->completed()->create(['column_id' => $columns[4]->id, 'created_by' => $getRandomMember()->id])
            ->each(function ($card) use ($labels, $getRandomMember) {
                $designLabel = $labels->firstWhere('name', 'Design');
                if ($designLabel) {
                    $card->labels()->attach($designLabel->id);
                }
                $card->assignees()->attach($getRandomMember()->id);
            });
    }
}

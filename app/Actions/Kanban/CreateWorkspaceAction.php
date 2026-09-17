<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\CreateWorkspaceDTO;
use App\Enums\Kanban\WorkspaceRole;
use App\Models\Kanban\Workspace;
use Illuminate\Support\Facades\DB;

class CreateWorkspaceAction
{
    public function execute(CreateWorkspaceDTO $dto): Workspace
    {
        return DB::transaction(function ()  use ($dto) {
            // Создание воркспейса
            $workspace = Workspace::create([
                'name' => $dto->name,
                'descritpion' => $dto->description,
                'owner_id' => $dto->ownerId,
            ]);

            // Добавление владельца как участника с ролью Owner
            $workspace->members()->attach($dto->ownerId, [
                'role' => WorkspaceRole::Owner->value,
                'joined_at' => now(),
            ]);

            return $workspace->fresh(['owner', 'members']);
        });
    }
}
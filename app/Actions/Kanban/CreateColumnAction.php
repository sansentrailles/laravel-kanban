<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\CreateColumnDTO;
use App\Models\Kanban\Column;
use Illuminate\Support\Facades\DB;

class CreateColumnAction
{
    public function execute(CreateColumnDTO $dto): Column
    {
        return DB::transaction(function () use ($dto) {
            return Column::create([
                'board_id' => $dto->boardId,
                'title' => $dto->title,
                'wip_limit' => $dto->wipLimit,
                'color' => $dto->color,
                'order' => $dto->order,
            ]);
        });
    }
}

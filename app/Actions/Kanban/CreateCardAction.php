<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\CreateCardDTO;
use App\Models\Kanban\Card;
use Illuminate\Support\Facades\DB;

class CreateCardAction
{
    public function execute(CreateCardDTO $dto): Card
    {
        return DB::transaction(function () use ($dto) {
            return Card::create([
                'column_id' => $dto->columnId,
                'title' => $dto->title,
            ]);
        });
    }
}

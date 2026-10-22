<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\UpdateColumnDTO;
use App\Models\Kanban\Column;

class UpdateColumnAction
{
    /**
     * Частичное обновление колонки: приходят только поля из запроса
     * (сейчас — color), остальные данные колонки не трогаем.
     */
    public function execute(Column $column, UpdateColumnDTO $dto): Column
    {
        if ($dto->attributes === []) {
            return $column;
        }

        $column->update($dto->attributes);

        return $column->fresh();
    }
}

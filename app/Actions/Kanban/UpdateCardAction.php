<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\UpdateCardDTO;
use App\Models\Kanban\Card;
use Illuminate\Support\Facades\DB;

class UpdateCardAction
{
    public function execute(Card $card, UpdateCardDTO $dto): Card
    {
        // Если в запросе не было полей для обновления, просто возвращаем модель
        if (empty($dto->attributes)) {
            return $card;
        }

        return DB::transaction(function () use ($card, $dto) {
            $card->update($dto->attributes);

            // Возвращаем свежую версию модели из БД
            return $card->fresh();
        });
    }
}

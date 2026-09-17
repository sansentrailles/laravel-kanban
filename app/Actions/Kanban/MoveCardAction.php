<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Support\Facades\DB;

final class MoveCardAction
{
    /**
     * Перемещает карточку в новую колонку и позициз
     */
    //  public function execute(Card $card, Column $targetColumn, ?Card $previousCard, ?Card $nextCard): Card
    public function execute(Card $card, Column $targetColumn, ?Card $prevCard, ?Card $nextCard): Card
    {
        return DB::transaction(function () use ($card, $prevCard, $targetColumn, $prevCard, $nextCard) {
            $newOrder = $this->calculateOrder($prevCard, $nextCard);

            $card->update([
                'column_id' => $targetColumn->id,
                'order' => $newOrder,
            ]);

            if ($targetColumn->isOverLimit()) {
                // Логика обработки превышения лимита
            }

            return $card->fresh();
        });
    }

    private function calculateOrder(?Card $prev, ?Card $next): string
    {
        // Если карточка первая в колонке
        if ($prev === null && $next === null) {
            return '1000000000.0000000000';
        }

        if ($prev === null) {
            // Вставляем перед первой карточкой
            return bcsub((string) $next->order, '100000000.0000000000', 10);
        }

        if ($next === null) {
            // Вставляем после последней карточки
            return bcadd((string) $prev->order, '100000000.0000000000', 10);
        }

        // Вставляем между двумя карточками (среднее арифметическое)
        return bcdiv(bcadd((string) $prev->order, (string) $next->order, 10), '2', 10);
    }
}
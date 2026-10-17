<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MoveCardAction
{
    /**
     * Перемещает карточку в новую колонку и позициз
     */
    //  public function execute(Card $card, Column $targetColumn, ?Card $previousCard, ?Card $nextCard): Card
    public function execute(Card $card, Column $targetColumn, ?Card $prevCard, ?Card $nextCard): Card
    {
        $isCrossColumnMove = (int) $card->column_id !== (int) $targetColumn->id;

        if ($isCrossColumnMove && $targetColumn->isOverLimit()) {
            throw ValidationException::withMessages([
                'wip_limit' => "WIP-лимит колонки «{$targetColumn->title}» достигнут ({$targetColumn->wip_limit}). Карточка не может быть перенесена.",
            ]);
        }

        return DB::transaction(function () use ($card, $targetColumn, $prevCard, $nextCard) {
            $newOrder = $this->calculateOrder($prevCard, $nextCard);

            $card->update([
                'column_id' => $targetColumn->id,
                'ord' => $newOrder,
            ]);

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
            return bcsub((string) $next->ord, '100000000.0000000000', 10);
        }

        if ($next === null) {
            // Вставляем после последней карточки
            return bcadd((string) $prev->ord, '100000000.0000000000', 10);
        }

        // Вставляем между двумя карточками (среднее арифметическое)
        return bcdiv(bcadd((string) $prev->ord, (string) $next->ord, 10), '2', 10);
    }
}

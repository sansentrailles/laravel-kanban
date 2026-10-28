<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\Events\Kanban\CardsReordered;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class MoveCardAction
{
    /** Шаг между соседними карточками при обычной вставке. */
    private const ORDER_STEP = '100000000.0000000000';

    /** Шаг при переиндексации: даёт большой запас на последующие вставки. */
    private const REBALANCE_STEP = 1000000000;

    /**
     * Перемещает карточку в новую колонку и вычисляет новый порядок (ord).
     */
    public function execute(Card $card, Column $targetColumn, ?Card $prevCard, ?Card $nextCard): Card
    {
        [$movedCard, $reorderedCards, $lockedColumn] = DB::transaction(function () use ($card, $targetColumn, $prevCard, $nextCard) {
            // Блокируем строку колонки, чтобы параллельные переносы в неё
            // проверяли WIP-лимит последовательно, а не одновременно.
            $lockedColumn = Column::whereKey($targetColumn->id)->lockForUpdate()->firstOrFail();

            // Блокируем саму карточку: два параллельных переноса одной карточки
            // не должны вычислять ord друг за другом по устаревшим данным.
            $lockedCard = Card::whereKey($card->id)->lockForUpdate()->firstOrFail();

            $isCrossColumnMove = (int) $lockedCard->column_id !== (int) $lockedColumn->id;

            if ($isCrossColumnMove && $lockedColumn->isOverLimit()) {
                throw ValidationException::withMessages([
                    'wip_limit' => "WIP-лимит колонки «{$lockedColumn->title}» достигнут ({$lockedColumn->wip_limit}). Карточка не может быть перенесена.",
                ]);
            }

            // Соседи могли измениться, пока запрос ждал блокировку колонки,
            // поэтому перечитываем их внутри транзакции по id.
            $prev = $prevCard !== null ? Card::whereKey($prevCard->id)->first() : null;
            $next = $nextCard !== null ? Card::whereKey($nextCard->id)->first() : null;

            $newOrder = $this->calculateOrder($prev, $next);
            $reorderedCards = null;

            if ($this->needsRebalance($newOrder, $prev, $next)) {
                $rebalance = $this->rebalanceColumn($lockedColumn, $lockedCard, $prev);
                $newOrder = $rebalance['ord'];
                $reorderedCards = $rebalance['cards'];
            }

            $lockedCard->update([
                'column_id' => $lockedColumn->id,
                'ord' => $newOrder,
            ]);

            return [$lockedCard->fresh(), $reorderedCards, $lockedColumn];
        });

        // Событие шлём после коммита и без toOthers(): ord нужны и автору
        // переноса, иначе его локальные значения останутся устаревшими.
        if ($reorderedCards !== null) {
            broadcast(new CardsReordered($lockedColumn, $reorderedCards));
        }

        return $movedCard;
    }

    private function calculateOrder(?Card $prev, ?Card $next): string
    {
        // Если карточка первая в колонке
        if ($prev === null && $next === null) {
            return '1000000000.0000000000';
        }

        if ($prev === null) {
            // Вставляем перед первой карточкой
            return bcsub((string) $next->ord, self::ORDER_STEP, 10);
        }

        if ($next === null) {
            // Вставляем после последней карточки
            return bcadd((string) $prev->ord, self::ORDER_STEP, 10);
        }

        // Вставляем между двумя карточками (среднее арифметическое)
        return bcdiv(bcadd((string) $prev->ord, (string) $next->ord, 10), '2', 10);
    }

    /**
     * Переиндексация нужна, если вычисленный ord вышел за границы соседей:
     * вставка в начало ушла в ноль/минус или среднее совпало с соседом
     * из-за исчерпанной точности decimal(20,10).
     */
    private function needsRebalance(string $order, ?Card $prev, ?Card $next): bool
    {
        if (bccomp($order, '0', 10) <= 0) {
            return true;
        }

        if ($prev !== null && bccomp($order, (string) $prev->ord, 10) <= 0) {
            return true;
        }

        return $next !== null && bccomp($order, (string) $next->ord, 10) >= 0;
    }

    /**
     * Перенумеровывает колонку с большим шагом и возвращает ord перемещаемой
     * карточки вместе со списком всех затронутых карточек.
     *
     * @return array{ord: string, cards: array<int, array{id: int, ord: string}>}
     */
    private function rebalanceColumn(Column $column, Card $movedCard, ?Card $prev): array
    {
        /** @var Collection<int, Card> $cards */
        $cards = $column->cards()
            ->whereKeyNot($movedCard->getKey())
            ->orderBy('ord')
            ->lockForUpdate()
            ->get();

        // Позиция вставки: сразу после prev, иначе — в начало колонки.
        $insertIndex = 0;

        if ($prev !== null) {
            $prevIndex = $cards->search(fn (Card $card) => $card->id === $prev->id);
            $insertIndex = $prevIndex === false ? $cards->count() : $prevIndex + 1;
        }

        $cards->splice($insertIndex, 0, [$movedCard]);

        $reordered = [];
        $movedOrder = null;

        foreach ($cards->values() as $index => $card) {
            $ord = (string) (($index + 1) * self::REBALANCE_STEP);

            $card->update(['ord' => $ord]);

            $reordered[] = ['id' => $card->id, 'ord' => $ord];

            if ($card->is($movedCard)) {
                $movedOrder = $ord;
            }
        }

        return ['ord' => $movedOrder ?? throw new LogicException('Moved card is missing from the rebalanced column.'), 'cards' => $reordered];
    }
}

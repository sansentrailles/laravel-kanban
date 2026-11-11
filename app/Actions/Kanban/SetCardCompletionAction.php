<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\Models\Kanban\Card;

class SetCardCompletionAction
{
    public function execute(Card $card, bool $completed): Card
    {
        $card->update([
            'completed_at' => $completed ? ($card->completed_at ?? now()) : null,
        ]);

        return $card;
    }
}

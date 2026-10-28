<?php

declare(strict_types=1);

use App\Enums\Kanban\CardPriority;
use App\Models\Kanban\Card;
use Illuminate\Support\Facades\DB;

it('converts legacy hight priority values to high', function () {
    // Регрессия: enum раньше хранил 'hight', и после исправления каст
    // priority => CardPriority::class падал бы на уже сохранённых карточках.
    $card = Card::factory()->create();

    DB::table('kanban_cards')
        ->where('id', $card->id)
        ->update(['priority' => 'hight']);

    $migration = require database_path('migrations/2026_10_28_114334_fix_card_priority_high_value.php');
    $migration->up();

    expect(DB::table('kanban_cards')->where('id', $card->id)->value('priority'))->toBe('high')
        ->and($card->refresh()->priority)->toBe(CardPriority::High);
});

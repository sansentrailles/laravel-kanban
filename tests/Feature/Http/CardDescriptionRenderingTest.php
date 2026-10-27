<?php

use App\Models\Kanban\Card;
use App\Models\User;
use Tests\TestCase;

it('renders card descriptions as escaped text instead of raw html', function () {
    /** @var TestCase $this */

    // У проекта нет JS-раннера, поэтому регрессию на stored XSS ловим
    // статически: v-html — единственный способ исполнить HTML из описания.
    $component = file_get_contents(resource_path('js/Components/Card/DescriptionEditor.vue'));

    expect($component)->not->toContain('v-html');
});

it('stores and returns card descriptions verbatim', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $description = '<img src=x onerror=alert(1)> **bold**';

    $this->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), [
            'description' => $description,
        ])
        ->assertOk()
        ->assertJsonPath('data.description', $description);

    expect($card->refresh()->description)->toBe($description);
});

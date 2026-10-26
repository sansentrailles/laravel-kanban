<?php

use App\Models\Kanban\Card;
use App\Models\Kanban\Checklist;
use App\Models\Kanban\ChecklistItem;
use App\Models\User;
use Tests\TestCase;

it('forbids creating a checklist for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();

    $this
        ->actingAs($user)
        ->postJson(route('cards.checklist.store', ['card' => $card->id]), [
            'title' => 'New checklist',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('kanban_checklists', [
        'card_id' => $card->id,
    ]);

    expect($card->checklists->count())->toBe(0);
});

it('forbids deleting a checklist for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['title' => 'New checklist', 'card_id' => $card->id]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.checklist.delete', ['checklist' => $checklist->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('kanban_checklists', [
        'card_id' => $card->id,
    ]);

    $checklist = $card->checklists()->firstOrFail();

    expect($checklist->title)->toBe('New checklist');
});

it('forbids adding an item for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['title' => 'New checklist', 'card_id' => $card->id]);

    $this
        ->actingAs($user)
        ->postJson(route('cards.checklist.items.store', ['checklist' => $checklist->id]))
        ->assertForbidden();

    $this->assertDatabaseMissing('kanban_checklist_items', [
        'checklist_id' => $checklist->id,
    ]);

    expect($checklist->items->count())->toBe(0);
});

it('forbids toggling an item for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['title' => 'New checklist', 'card_id' => $card->id]);
    $item = ChecklistItem::factory()->create(['content' => 'New Item', 'checklist_id' => $checklist->id]);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.checklists.items.toggle', ['checklist' => $checklist->id, 'item' => $item->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('kanban_checklist_items', [
        'checklist_id' => $checklist->id,
    ]);

    $oldItem = $checklist->items()->firstOrFail();

    expect($oldItem->is_completed)->toBeFalse();
});

it('forbids deleting an item for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['title' => 'New checklist', 'card_id' => $card->id]);
    $item = ChecklistItem::factory()->create(['content' => 'New Item', 'checklist_id' => $checklist->id]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.checklists.items.delete', ['checklist' => $checklist->id, 'item' => $item->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('kanban_checklist_items', [
        'checklist_id' => $checklist->id,
    ]);

    $oldItem = $checklist->items()->firstOrFail();
    expect($oldItem->content)->toBe('New Item');
});

it('returns 404 when the item belongs to another checklist', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['title' => 'New checklist', 'card_id' => $card->id]);
    $otherChecklist = Checklist::factory()->create(['title' => 'New checklist', 'card_id' => $card->id]);
    $otherItem = ChecklistItem::factory()->create(['content' => 'New Other Item', 'checklist_id' => $otherChecklist->id]);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.checklists.items.delete', ['checklist' => $checklist->id, 'item' => $otherItem->id]))
        ->assertNotFound();

    $oldOtherItem = $otherChecklist->items()->firstOrFail();
    expect($oldOtherItem->content)->toBe('New Other Item');
});

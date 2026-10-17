<?php

use App\Actions\Kanban\CreateCommentAction;
use App\DTO\Kanban\CreateCommentDTO;
use App\Models\Kanban\Card;
use App\Models\User;

it('creates a comment attached to a card and its author', function () {
    $card = Card::factory()->create();
    $user = User::factory()->create();

    $comment = (new CreateCommentAction)->execute(
        new CreateCommentDTO(content: 'Looks good to me', cardId: $card->id, userId: $user->id),
    );

    $this->assertDatabaseHas('kanban_comments', [
        'id' => $comment->id,
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => 'Looks good to me',
    ]);
});

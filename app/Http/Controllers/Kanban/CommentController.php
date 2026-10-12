<?php

namespace App\Http\Controllers\Kanban;

use App\Http\Controllers\Controller;
use App\Actions\Kanban\CreateCommentAction;
use App\DTO\Kanban\CreateCommentDTO;
use App\Events\Kanban\CardUpdated;
use App\Http\Requests\Kanban\StoreCommentRequest;
use App\Http\Resources\Kanban\CommentResource;
use App\Models\Kanban\Card;

class CommentController extends Controller
{
    public function __construct(
        private readonly CreateCommentAction $createCommentAction,
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request, Card $card)
    {
        $dto = new CreateCommentDTO(
            cardId: $card->id,
            userId: $request->user()->id,
            content: $request->validated('content'),
        );

        $comment = $this->createCommentAction->execute($dto);
        $comment->load('user');

        $card = $comment->card;
        $card->fresh()->load(['checklists.items', 'labels', 'assignees', 'comments']);

        broadcast(new CardUpdated($card))->toOthers();

        return response()->json([
            'success' => true,
            'createdComment' => new CommentResource($comment),
        ]);
    }
}

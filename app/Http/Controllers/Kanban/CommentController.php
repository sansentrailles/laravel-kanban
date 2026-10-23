<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateCommentAction;
use App\DTO\Kanban\CreateCommentDTO;
use App\Events\Kanban\CommentCreated;
use App\Events\Kanban\CommentDeleted;
use App\Events\Kanban\CommentUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreCommentRequest;
use App\Http\Requests\Kanban\UpdateCommentRequest;
use App\Http\Resources\Kanban\CommentResource;
use App\Models\Kanban\Card;
use App\Models\Kanban\Comment;

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
            content: $request->validated('content'),
            cardId: $card->id,
            userId: $request->user()->id,
        );

        $comment = $this->createCommentAction->execute($dto);
        $comment->load('user');

        // Отдельное событие с минимальным payload: ни карточку, ни её
        // остальные связи не гоняем по WebSocket.
        broadcast(new CommentCreated($comment, $card))->toOthers();

        return response()->json([
            'success' => true,
            'createdComment' => new CommentResource($comment),
        ]);
    }

    public function destroy(Card $card, Comment $comment)
    {
        $commentId = $comment->id;
        $comment->delete();

        broadcast(new CommentDeleted($commentId, $card))->toOthers();

        return response()->json([
            'success' => true,
        ]);
    }

    public function update(UpdateCommentRequest $request, Card $card, Comment $comment)
    {
        $comment->update($request->validated());

        broadcast(new CommentUpdated($comment, $card))->toOthers();

        return response()->json([
            'success' => true,
        ]);
    }
}

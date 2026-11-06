<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateCommentAction;
use App\Actions\Kanban\RecordCardActivityAction;
use App\DTO\Kanban\CreateCommentDTO;
use App\DTO\Kanban\TextSnapshotDTO;
use App\Enums\Kanban\CardActivityType;
use App\Events\Kanban\CommentCreated;
use App\Events\Kanban\CommentDeleted;
use App\Events\Kanban\CommentUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreCommentRequest;
use App\Http\Requests\Kanban\UpdateCommentRequest;
use App\Http\Resources\Kanban\CommentResource;
use App\Models\Kanban\Card;
use App\Models\Kanban\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(
        private readonly CreateCommentAction $createCommentAction,
        private readonly RecordCardActivityAction $recordCardActivityAction,
    ) {}

    /**
     * Комментарии грузятся лениво при открытии карточки: в payload доски
     * их нет, потому что у карточки может быть очень много комментариев.
     */
    public function index(Card $card)
    {
        Gate::authorize('view', $card);

        $comments = $card->comments()->with('user')->get();

        return response()->json([
            'comments' => CommentResource::collection($comments)->resolve(),
            'comments_count' => $comments->count(),
        ]);
    }

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

        $actor = $request->user();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $actor,
            type: CardActivityType::CommentCreated,
            subjectId: $comment->id,
            changes: [
                'content' => TextSnapshotDTO::from($comment->content),
            ],
            context: [],
        );

        return response()->json([
            'success' => true,
            'createdComment' => new CommentResource($comment),
        ]);
    }

    public function update(UpdateCommentRequest $request, Card $card, Comment $comment)
    {
        Gate::authorize('update', $comment);

        $data = $request->validated();
        $oldContent = $comment->content;

        if ($oldContent === $data['content']) {
            return response()->json(['success' => true]);
        }

        $comment->update([
            ...$data,
            'is_edited' => true,
        ]);

        broadcast(new CommentUpdated($comment, $card))->toOthers();

        $actor = $request->user();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $actor,
            type: CardActivityType::CommentUpdated,
            subjectId: $comment->id,
            changes: [
                'content' => [
                    'from' => TextSnapshotDTO::from($oldContent),
                    'to' => TextSnapshotDTO::from($comment->content),
                ],
            ],
            context: [],
        );

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy(Card $card, Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $commentId = $comment->id;
        $content = $comment->content;
        $comment->delete();

        broadcast(new CommentDeleted($commentId, $card))->toOthers();

        $actor = Auth::user();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $actor,
            type: CardActivityType::CommentDeleted,
            subjectId: $comment->id,
            changes: [
                'content' => TextSnapshotDTO::from($content),
            ],
            context: [],
        );

        return response()->json([
            'success' => true,
        ]);
    }
}

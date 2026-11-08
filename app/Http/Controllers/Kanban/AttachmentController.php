<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\RecordCardActivityAction;
use App\Actions\Kanban\UploadAttachmentAction;
use App\Enums\Kanban\CardActivityType;
use App\Events\Kanban\AttachmentDeleted;
use App\Events\Kanban\AttachmentUploaded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreAttachmentRequest;
use App\Http\Resources\Kanban\AttachmentResource;
use App\Models\Kanban\Attachment;
use App\Models\Kanban\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly UploadAttachmentAction $uploadAction,
        private readonly RecordCardActivityAction $recordCardActivityAction,
    ) {}

    public function store(StoreAttachmentRequest $request, Card $card): JsonResponse
    {
        $attachment = $this->uploadAction->execute(
            $card,
            $request->file('file'),
            $request->user()
        );

        broadcast(new AttachmentUploaded($attachment, $card))->toOthers();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $request->user(),
            type: CardActivityType::AttachmentUploaded,
            subjectId: $attachment->id,
            context: [
                'name' => $attachment->name,
                'mime' => $attachment->mime_type,
                'size' => $attachment->size,
            ],
        );

        return response()->json([
            'success' => true,
            'attachment' => new AttachmentResource($attachment),
            'attachments_count' => $card->attachments()->count(),
        ], 201);
    }

    public function destroy(Request $request, Card $card, Attachment $attachment): JsonResponse
    {
        Gate::authorize('delete', $attachment);

        if ($card->id !== $attachment->card_id) {
            abort(404);
        }

        // Сначала строка в БД, затем файл: если удаление записи упадёт,
        // вложение останется рабочим, а не превратится в битую ссылку.
        $path = $attachment->path;

        $attachmentId = $attachment->id;
        $meta = [
            'name' => $attachment->name,
            'mime' => $attachment->mime_type,
            'size' => $attachment->size,
        ];

        $attachment->delete();

        Storage::disk('public')->delete($path);

        broadcast(new AttachmentDeleted($card, $attachmentId))->toOthers();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $request->user(),
            type: CardActivityType::AttachmentDeleted,
            subjectId: $attachmentId,
            context: $meta,
        );

        return response()->json([
            'success' => true,
            'attachments_count' => $card->attachments()->count(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\UploadAttachmentAction;
use App\Events\Kanban\AttachmentDeleted;
use App\Events\Kanban\AttachmentUploaded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreAttachmentRequest;
use App\Http\Resources\Kanban\AttachmentResource;
use App\Models\Kanban\Attachment;
use App\Models\Kanban\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly UploadAttachmentAction $uploadAction
    ) {}

    public function store(StoreAttachmentRequest $request, Card $card): JsonResponse
    {
        $attachment = $this->uploadAction->execute(
            $card,
            $request->file('file'),
            $request->user()
        );

        broadcast(new AttachmentUploaded($attachment, $card))->toOthers();

        return response()->json([
            'success' => true,
            'attachment' => new AttachmentResource($attachment),
            'attachments_count' => $card->attachments()->count(),
        ], 201);
    }

    public function destroy(Card $card, Attachment $attachment): JsonResponse
    {
        Gate::authorize('delete', $attachment);

        if ($card->id !== $attachment->card_id) {
            abort(404);
        }

        Storage::disk('public')->delete($attachment->path);

        $attachment->delete();

        broadcast(new AttachmentDeleted($card, $attachment->id))->toOthers();

        return response()->json([
            'success' => true,
            'attachments_count' => $card->attachments()->count(),
        ]);
    }
}

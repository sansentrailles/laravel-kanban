<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Http\Controllers\Controller;
use App\Http\Resources\Kanban\CardActivityResource;
use App\Models\Kanban\Card;
use App\Models\Kanban\CardActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CardActivityController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    public function index(Request $request, Card $card): JsonResponse
    {
        Gate::authorize('view', $card);

        $perPage = max(1, min(self::MAX_PER_PAGE, $request->integer('per_page', self::DEFAULT_PER_PAGE)));

        $query = CardActivity::query()->where('card_id', $card->id);

        $cursor = $request->query('cursor');

        if (is_string($cursor) && $cursor !== '') {
            [$createdAt, $cursorId] = $this->decodeCursor($cursor);

            $query->where(function (Builder $query) use ($createdAt, $cursorId): void {
                $query->where('created_at', '<', $createdAt)
                    ->orWhere(function (Builder $query) use ($createdAt, $cursorId): void {
                        $query->where('created_at', '=', $createdAt)
                            ->where('_id', '<', $cursorId);
                    });
            });
        }

        $activities = $query
            ->orderByDesc('created_at')
            ->orderByDesc('_id')
            ->limit($perPage + 1)
            ->get();

        $hasMore = $activities->count() > $perPage;
        $activities = $activities->take($perPage);

        $nextCursor = null;

        if ($hasMore && $activities->isNotEmpty()) {
            $nextCursor = $this->encodeCursor($activities->last());
        }

        return response()->json([
            'activities' => CardActivityResource::collection($activities)->resolve(),
            'next_cursor' => $nextCursor,
        ]);
    }

    private function encodeCursor(CardActivity $activity): string
    {
        // Миллисекунды, а не ISO строка: Mongo хранит дату с точностью до мс,
        // ISO без долей секунды терял бы порядок событий внутри одной секунды
        return base64_encode(json_encode([
            'created_at' => $activity->created_at->getTimestampMs(),
            'id' => (string) $activity->_id,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{0: Carbon, 1: string}
     */
    private function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);
        $payload = $decoded === false ? null : json_decode($decoded, true);

        if (
            ! is_array($payload)
            || ! isset($payload['created_at'], $payload['id'])
            || ! is_numeric($payload['created_at'])
            || ! is_string($payload['id'])
            || preg_match('/^[a-f0-9]{24}$/i', $payload['id']) !== 1
        ) {
            throw $this->invalidCursor();
        }

        return [
            Carbon::createFromTimestampMs((int) $payload['created_at']),
            $payload['id'],
        ];
    }

    private function invalidCursor(): ValidationException
    {
        return ValidationException::withMessages([
            'cursor' => 'Некорректный курсор пагинации',
        ]);
    }
}

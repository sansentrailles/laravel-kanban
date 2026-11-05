<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\CardActivityDTO;
use App\Jobs\Kanban\PersistCardActivity;
use App\Models\Kanban\CardActivity;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RecordCardActivityAction
{
    public function execute(CardActivityDTO $dto): void
    {
        $payload = [
            'schema_version' => CardActivity::SCHEMA_VERSION,
            'event_id' => (string) Str::uuid(),
            'type' => $dto->type->value,
            'workspace_id' => $dto->workspaceId,
            'board_id' => $dto->boardId,
            'column_id' => $dto->columnId,
            'card_id' => $dto->cardId,
            'card_title' => $dto->cardTitle,
            'subject_type' => $dto->type->subjectType(),
            'subject_id' => $dto->subjectId ?? $dto->cardId,
            'actor' => [
                'id' => $dto->actorId,
                'name' => $dto->actorName,
            ],
            'changes' => $this->normalize($dto->changes),
            'context' => $this->normalize($dto->context),
            'created_at' => now(),
        ];

        try {
            PersistCardActivity::dispatch($payload)
                ->onQueue('activity')
                ->afterCommit();
        } catch (Throwable $e) {
            Log::error('Не удалось поставить запись активности карточки в очередь', [
                'event_id' => $payload['event_id'],
                'type' => $payload['type'],
                'card_id' => $payload['card_id'],
            ]);
        }
    }

    /**
     * Приведение changes/context к BSON-совместимым скалярам
     * enum -> value, дата - ISO строка, вложенные массивы - рекурсивно
     * 
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        return array_map(function (mixed $value) {
            if ($value instanceof BackedEnum) {
                return $value->value;
            }

            if ($value instanceof DateTimeInterface) {
                return $value->format(DATE_ATOM);
            }

            if ($value instanceof Arrayable) {
                return $this->normalize($value->toArray());
            }


            if (is_array($value)) {
                return $this->normalize($value);
            }

            return $value;
        }, $data);
    }
}
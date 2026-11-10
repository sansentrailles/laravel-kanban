<?php

namespace App\Jobs\Kanban;

use App\Events\Kanban\ActivityCreated;
use App\Http\Resources\Kanban\CardActivityResource;
use App\Models\Kanban\CardActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;

class PersistCardActivity implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 60, 180];

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $activity = CardActivity::query()->createOrFirst(
            ['event_id' => $this->payload['event_id']],
            $this->payload,
        );

        // При повторе job'а createOrFirst вернёт уже существующую запись —
        // событие повторно не отправляем.
        if (! $activity->wasRecentlyCreated) {
            return;
        }

        // resolve(new Request()) вместо resolve(): job выполняется в воркере,
        // где контейнер не обязан содержать текущий HTTP-запрос.
        broadcast(new ActivityCreated(
            workspaceId: (int) $activity->workspace_id,
            cardId: (int) $activity->card_id,
            activity: CardActivityResource::make($activity)->resolve(new Request),
        ));
    }
}

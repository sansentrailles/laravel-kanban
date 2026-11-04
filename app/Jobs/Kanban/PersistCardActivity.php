<?php

namespace App\Jobs\Kanban;

use App\Models\Kanban\CardActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        CardActivity::query()->createOrFirst(
            ['event_id' => $this->payload['event_id']],
            $this->payload,
        );
    }
}

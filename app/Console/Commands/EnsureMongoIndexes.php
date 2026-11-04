<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('app:ensure-mongo-indexes')]
#[Description('Command description')]
class EnsureMongoIndexes extends Command
{
    protected $signature = 'mongo:ensure-indexes';
    protected $description = 'Создает индексы для коллекций MongoDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection = DB::connection('mongodb')->getCollection('card_activities');

        $indexes = [
            [
                'key' => ['card_id' => 1, 'created_at' => -1, '_id' => -1],
                'name' => 'card_id_created_at',
            ],
            [
                'key' => ['board_id' => 1, 'created_at' => -1, '_id' => -1],
                'name' => 'board_id_created_at',
            ],
            [
                'key' => ['workspace_id' => 1, 'created_at' => -1, '_id' => -1],
                'name' => 'workspace_id_created_at',
            ],
            [
                'key' => ['event_id' => 1],
                'name' => 'event_id_unique',
                'unique' => true,
            ],
        ];

        foreach ($indexes as $index) {
            $options = ['name' => $index['name']];

            if (isset($index['unique'])) {
                $options['unique'] = true;
            }

            try {
                $collection->createIndex($index['key'], $options);
                $this->line("Индекс {$index['name']} готов");
            } catch (Throwable $e) {
                $this->error("Не удалось создать индекс {$index['name']}: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}

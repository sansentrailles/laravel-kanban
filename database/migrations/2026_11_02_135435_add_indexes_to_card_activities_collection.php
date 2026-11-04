<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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

            // ВАЖНО ДЛЯ ПРОДАКШЕНА: 
            // Если коллекция уже огромная, добавление индекса может заблокировать запись.
            // В современных версиях MongoDB это происходит в фоне, но можно добавить 'background' => true 
            // или 'commitQuorum' в $options для явного контроля, если потребуется.

            try {
                $collection->createIndex($index['key'], $options);
            } catch (Throwable $e) {
                throw new \RuntimeException("Не удалось создать индекс {$index['name']}: {$e->getMessage()}", 0, $e);
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $collection = DB::connection('mongodb')->getCollection('card_activities');

        $collection->dropIndex('card_id_created_at');
        $collection->dropIndex('board_id_created_at');
        $collection->dropIndex('workspace_id_created_at');
        $collection->dropIndex('event_id_unique');
    }
};

<?php

namespace App\Models\Kanban;

use MongoDB\Laravel\Eloquent\Model;

class CardActivity extends Model
{
    public const SCHEMA_VERSION = 1;
    public const UPDATED_AT = null;

    protected $connection = 'mongodb';

    protected $collection = 'card_activities';

    protected $fillable = [
        'schema_version',
        'event_id',
        'type',
        'workspace_id',
        'board_id',
        'column_id',
        'card_id',
        'card_title',
        'subject_type',
        'subject_id',
        'actor',
        'changes',
        'context',
        'created_at',

    ];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'workspace_id' => 'integer',
            'board_id' => 'integer',
            'card_id' => 'integer',
            'subject_id' => 'integer',
        ];
    }
}

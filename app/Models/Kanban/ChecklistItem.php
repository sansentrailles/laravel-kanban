<?php

namespace App\Models\Kanban;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Checklist|null $checklist
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistItem query()
 *
 * @mixin \Eloquent
 */
class ChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id',
        'content',
        'is_completed',
        'order',
    ];

    public function casts(): array
    {
        return [
            'checklist_id' => 'integer',
            'is_completed' => 'integer',
            'order' => 'integer',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }
}

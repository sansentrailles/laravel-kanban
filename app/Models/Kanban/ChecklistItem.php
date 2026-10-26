<?php

namespace App\Models\Kanban;

use Database\Factories\Kanban\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
#[Table('kanban_checklist_items')]
class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory;

    protected $fillable = [
        'checklist_id',
        'content',
        'is_completed',
        'ord',
    ];

    public function casts(): array
    {
        return [
            'checklist_id' => 'integer',
            'is_completed' => 'boolean',
            'ord' => 'integer',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }
}

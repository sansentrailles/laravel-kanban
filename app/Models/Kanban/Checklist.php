<?php

namespace App\Models\Kanban;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    protected $fillable = [
        'card_id',
        'title',
        'order'
    ];

    public function casts(): array
    {
        return [
            'card_id' => 'integer',
            'order' => 'integer',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('order');
    }

    /**
     * Прогресс выполнения чеклиста
     */
    public function getProgressAttribute(): float
    {
        $total = $this->items()->count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->items()->where('is_completed', true)->count();

        return round($completed / $total, 2);
    }
}

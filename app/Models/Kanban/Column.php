<?php

namespace App\Models\Kanban;

use Database\Factories\Kanban\ColumnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read Board|null $board
 *
 * @method static \Database\Factories\Kanban\ColumnFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column visible()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column withoutTrashed()
 *
 * @property int $id
 * @property int $board_id
 * @property string $title
 * @property string|null $color
 * @property int $order
 * @property bool $is_hidden
 * @property int|null $wip_limit
 * @property array<array-key, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereBoardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Column whereWipLimit($value)
 *
 * @mixin \Eloquent
 */
class Column extends Model
{
    /** @use HasFactory<ColumnFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'kanban_columns';

    protected $filleable = [
        'board_id',
        'title',
        'color',
        'order',
        'is_hidden',
        'wip_limit',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'board_id' => 'integer',
            'order' => 'integer',
            'is_hidden' => 'boolean',
            'wip_limit' => 'integer',
            'settings' => 'array',
        ];
    }

    // ─────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Карточки в колонке. Всегда отсортированные
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('order');
    }

    // ─────────────────────────────────────────────
    //  Scopes
    // ─────────────────────────────────────────────

    /**
     * Только видимые колонки (для отображения на доске)
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', true);
    }

    /**
     * Сортировка по порядку
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // ─────────────────────────────────────────────
    //  Helpers & Business Logic
    // ─────────────────────────────────────────────

    /**
     * Превышен ли WIP-лимит
     */
    public function isOverLimit(): bool
    {
        if (is_null($this->wip_limit)) {
            return false;
        }

        return $this->cards()->count() >= $this->wip_limit;
    }

    /**
     * Сколько еще карточек можно добавить до лимита
     */
    public function remainWipCapacity(): int
    {
        if (is_null($this->wip_limit)) {
            return PHP_INT_MAX;
        }

        return max(0, $this->wip_limit - $this->cards()->count());
    }

    /**
     * Получить настройки колонки
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
}

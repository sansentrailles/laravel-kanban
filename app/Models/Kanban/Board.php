<?php

namespace App\Models\Kanban;

use App\Enums\Kanban\BoardVisibility;
use App\Models\User;
use Database\Factories\Kanban\BoardFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property mixed $visibility
 * @property-read Collection<int, Column> $columns
 * @property-read int|null $columns_count
 * @property-read User|null $creator
 * @property-read Workspace|null $workspace
 *
 * @method static \Database\Factories\Kanban\BoardFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board withoutTrashed()
 *
 * @property int $id
 * @property int $workspace_id
 * @property int|null $created_by
 * @property string|null $uuid
 * @property string $name
 * @property string|null $description
 * @property string|null $color
 * @property string|null $icon
 * @property int $order
 * @property array<array-key, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board whereWorkspaceId($value)
 *
 * @mixin \Eloquent
 */
#[Table('kanban_boards')]
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'uuid',
        'name',
        'description',
        'color',
        'icon',
        'order',
        'visibility',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'workspace_id' => 'integer',
            'created_by' => 'integer',
            'order' => 'integer',
            'visibility' => BoardVisibility::class,
            'settings' => 'array',
        ];
    }

    /**
     * Автоматическая генерация uuid при создании
     */
    protected static function booted(): void
    {
        parent::boot();
        static::creating(function (self $board) {
            if (empty($board->uuid)) {
                $board->uuid = Str::uuid()->toString();
            }
        });
    }

    // ─────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('order');
    }

    /**
     * Связь HasManyThrough для получения всех карточек доски через колонки.
     * Это позволяет делать ->withCount('cards') напрямую на доске.
     */
    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(
            Card::class,
            Column::class,
            'board_id',    // Foreign key on columns table
            'column_id',   // Foreign key on cards table
            'id',          // Local key on boards table
            'id'           // Local key on columns table
        );
    }

    /**
     * Получение настроек с дефолтными значениями
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Обновить настройку
     */
    public function updateSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;

        $this->settings = $settings;
        $this->save();
    }

    /**
     * Скоуп для сортировке в сайдбаре
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}

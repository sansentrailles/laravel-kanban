<?php

namespace App\Models\Kanban;

use App\Models\User;
use App\Enums\Kanban\BoardVisibility;
use Database\Factories\Kanban\BoardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property mixed $visibility
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kanban\Column> $columns
 * @property-read int|null $columns_count
 * @property-read User|null $creator
 * @property-read \App\Models\Kanban\Workspace|null $workspace
 * @method static \Database\Factories\Kanban\BoardFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Board withoutTrashed()
 * @mixin \Eloquent
 */
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'kanban_boards';

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
        static::created(function (self $board) {
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

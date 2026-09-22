<?php

namespace App\Models\Kanban;

use App\Enums\Kanban\CardPriority;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * @property CardPriority $priority
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $assignee
 * @property-read int|null $assignee_count
 * @property-read \App\Models\Kanban\Column|null $column
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kanban\Label> $labels
 * @property-read int|null $labels_count
 * @method static \Database\Factories\Kanban\CardFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card withoutTrashed()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kanban\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kanban\Checklist> $checklists
 * @property-read int|null $checklists_count
 * @property int $id
 * @property int $column_id
 * @property int|null $created_by
 * @property string $title
 * @property string|null $description
 * @property numeric $order
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property string|null $completed_at
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereColumnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $assignees
 * @property-read int|null $assignees_count
 * @mixin \Eloquent
 */
class Card extends Model
{
    /** @use HasFactory<\Database\Factories\Kanban\CardFactory> */
    use HasFactory, SoftDeletes;
    
    protected $table = 'kanban_cards';

    protected $fillable = [
        'column_id',
        'created_by',
        'title',
        'description',
        'order',
        'priority',
        'due_date',
        'start_date',
        'completed_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'column_id' => 'integer',
            'created_by' => 'integer',
            'order' => 'decimal:10',
            'priority' => CardPriority::class,
            'due_date' => 'date',
            'start_date' => 'date',
            'completed_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    // ─────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────
    
    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'kanban_card_labels', 'card_id', 'label_id')
            ->withTimestamps();
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kanban_card_user', 'card_id', 'user_id')
            ->withTimestamps();
    }

    // public function comments(): HasMany
    // {
    //     return $this->hasMany(Comment::class);
    // }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    // ─────────────────────────────────────────────
    //  Scopes
    // ─────────────────────────────────────────────

    public function scopeOrdered($query)
    {
        return $query->order('order');
    }

    public function scopOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereNull('completed_at');
    }

    // ─────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->is_completed_at === null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function markAsCompleted(): void
    {
        $this->update(['is_completed' => now()]);
    }
}

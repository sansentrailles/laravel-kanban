<?php

namespace App\Models\Kanban;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read \App\Models\Kanban\Workspace|null $workspace
 * @method static \Database\Factories\Kanban\LabelFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kanban\Card> $cards
 * @property-read int|null $cards_count
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $color
 * @property string|null $description
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Label whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class Label extends Model
{
    /** @use HasFactory<\Database\Factories\Kanban\LabelFactory> */
    use HasFactory;

    protected $table = 'kanban_labels';

    protected $fillable = [
        'workspace_id',
        'name',
        'color',
        'description',
        'order'
    ];

    protected function casts(): array
    {
        return [
            'workspace_id' => 'integer',
            'order' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(
            Card::class,
            'kanban_card_label',
            'label_id',
            'card_id',
        )->withTimestamps();
    }
}

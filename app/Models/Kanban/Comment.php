<?php

namespace App\Models\Kanban;

use App\Models\User;
use Database\Factories\Kanban\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kanban_comments')]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'card_id',
        'user_id',
        'content',
        'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'card_id' => 'integer',
            'is_edited' => 'boolean',
        ];
    }

    // ─────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    public function chronological($query)
    {
        $query->orderBy('created_at', 'asc');
    }
}

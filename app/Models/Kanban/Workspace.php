<?php

namespace App\Models\Kanban;

use App\Enums\Kanban\WorkspaceRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Workspace extends Model
{
    /** @use HasFactory<\Database\Factories\Kanban\WorkspaceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
        'settigns'
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'owner_id' => 'integer',
        ];
    }

    /**
     * Автогенерация slug при создание
     * @return void
     */
    public static function booter(): void
    {
        static::creating(function (self $workspace): void {
            if (empty($workspace->slug)) {
                $workspace->slug = self::generateUniqueSlug($workspace->name);
            }
        });
    }

    // ─────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongs(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
                User::class,
                'kanban_workspace_user',
                'workspace_id',
                'user_id'
            )
            ->withPivot(['role', 'joined_at', 'invite_token'])
            ->withTimestamps();
    }

    // public function boards(): HasMany
    // {
    //     return $this->hasMany(Board::class);
    // }

    // ─────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────
    
    public function getRoleForUser(User $user): ?WorkspaceRole
    {
        if ($this->owner_id === $user->id) {
            return WorkspaceRole::Owner;
        }

        $pivot = $this->members()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot;
        
        return $pivot?->role ? WorkspaceRole::from($pivot->role) : null;
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function hasMember(User $user)
    {
        return $this->isOwner($user) || $this->members()->where('user_id', $user->id)->exists();
    }

    public function memberCount(): int
    {
        return $this->members()->count() + 1; // +1 за owner
    }

    /**
     * Генерирует уникальный slug на основе имени
     */
    public function generateUniqueSlug(string $name, int $attempt = 0): string
    {
        $base = Str::slug($name);
        $slug = $attempt === 0 ? $base : "{$base}-{$attempt}";

        return static::where('slug', $slug)->exists()
            ? self::generateUniqueSlug($name, $attempt + 1)
            : $slug;
    }
}

<?php

namespace App\Models\Kanban;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read \App\Models\Kanban\Card|null $card
 * @property-read string $formatted_size
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment query()
 * @mixin \Eloquent
 */
class Attachment extends Model
{
    protected $fillable = [
        'card_id',
        'user_id',
        'name',
        'path',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'card_id' => 'integer',
            'user_id' => 'integer',
            'size' => 'integer',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Размер файла в человеческом формате
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        $units = ['B', 'Kb', 'Mb', 'Gb', 'Tb'];
        $bytes = max($this->size, 0);
        $pow = floor(($bytes ? log($bytes): 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function getUrl(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}

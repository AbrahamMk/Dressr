<?php

namespace App\Models;

use Database\Factories\OutfitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outfit extends Model
{
    /** @use HasFactory<OutfitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'occasion',
        'season',
        'score',
        'source',
        'reasoning',
        'is_favorite',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'is_favorite' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outfitItems(): HasMany
    {
        return $this->hasMany(OutfitItem::class);
    }

    /**
     * @return HasMany<OutfitItem, $this>
     */
    public function items(): HasMany
    {
        return $this->outfitItems();
    }
}

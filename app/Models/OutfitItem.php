<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutfitItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'outfit_id',
        'item_id',
        'slot',
    ];

    public function outfit(): BelongsTo
    {
        return $this->belongsTo(Outfit::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

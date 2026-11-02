<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WearLogItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'wear_log_id',
        'item_id',
    ];

    public function wearLog(): BelongsTo
    {
        return $this->belongsTo(WearLog::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

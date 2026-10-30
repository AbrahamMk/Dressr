<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'subcategory',
        'primary_color_hex',
        'secondary_color_hex',
        'color_family',
        'pattern',
        'material',
        'seasons',
        'formality',
        'fit_type',
        'stretch',
        'cut_tags',
        'size_label',
        'g_chest_width',
        'g_waist_width',
        'g_hip_width',
        'g_length',
        'g_inseam',
        'g_shoulder_width',
        'photo_path',
        'thumb_path',
        'is_active',
        'notes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'photo_url',
        'thumb_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seasons' => 'array',
            'cut_tags' => 'array',
            'formality' => 'integer',
            'is_active' => 'boolean',
            'g_chest_width' => 'decimal:1',
            'g_waist_width' => 'decimal:1',
            'g_hip_width' => 'decimal:1',
            'g_length' => 'decimal:1',
            'g_inseam' => 'decimal:1',
            'g_shoulder_width' => 'decimal:1',
        ];
    }

    /**
     * The user the item belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public URL of the full-size photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path
            ? Storage::disk('public')->url($this->photo_path)
            : null;
    }

    /**
     * Public URL of the thumbnail.
     */
    public function getThumbUrlAttribute(): ?string
    {
        return $this->thumb_path
            ? Storage::disk('public')->url($this->thumb_path)
            : null;
    }
}

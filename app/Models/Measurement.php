<?php

namespace App\Models;

use Database\Factories\MeasurementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    /** @use HasFactory<MeasurementFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'measured_on',
        'height_cm',
        'weight_kg',
        'neck_cm',
        'shoulder_width_cm',
        'chest_cm',
        'waist_cm',
        'hips_cm',
        'inseam_cm',
        'arm_length_cm',
        'thigh_cm',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'measured_on' => 'date',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:1',
            'neck_cm' => 'decimal:1',
            'shoulder_width_cm' => 'decimal:1',
            'chest_cm' => 'decimal:1',
            'waist_cm' => 'decimal:1',
            'hips_cm' => 'decimal:1',
            'inseam_cm' => 'decimal:1',
            'arm_length_cm' => 'decimal:1',
            'thigh_cm' => 'decimal:1',
        ];
    }

    /**
     * The user the measurement belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

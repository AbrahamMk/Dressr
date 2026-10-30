<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The body measurements recorded by the user, newest first.
     */
    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class)
            ->orderByDesc('measured_on')
            ->orderByDesc('id');
    }

    /**
     * The most recent body measurement, if any.
     */
    public function currentMeasurement(): ?Measurement
    {
        return $this->measurements()->first();
    }

    /**
     * The wardrobe items owned by the user.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}

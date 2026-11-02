<?php

namespace App\Services\Outfit;

class OutfitScore
{
    /**
     * @param  array<string, float>  $factors  each 0..1
     */
    public function __construct(
        public readonly float $total,
        public readonly array $factors,
    ) {}

    /**
     * @return array{total: float, factors: array<string, float>}
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'factors' => $this->factors,
        ];
    }
}

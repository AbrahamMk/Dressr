<?php

namespace App\Services\Fit;

class FitResult
{
    /**
     * @param  list<array{pair: string, ease: float|null, label: string, body: float|null, garment: float|null}>  $eases
     */
    public function __construct(
        public readonly ?float $score,
        public readonly string $label,
        public readonly array $eases,
    ) {}

    /**
     * @return array{score: float|null, label: string, eases: list<array{pair: string, ease: float|null, label: string, body: float|null, garment: float|null}>}
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'label' => $this->label,
            'eases' => $this->eases,
        ];
    }
}

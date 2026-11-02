<?php

namespace App\Services\Silhouette;

class SilhouetteResult
{
    /**
     * @param  list<string>  $favored
     * @param  list<string>  $avoided
     */
    public function __construct(
        public readonly ?string $shape,
        public readonly ?string $label,
        public readonly ?string $explanation,
        public readonly array $favored = [],
        public readonly array $avoided = [],
    ) {}

    /**
     * @return array{shape: string|null, label: string|null, explanation: string|null, favored: list<string>, avoided: list<string>}
     */
    public function toArray(): array
    {
        return [
            'shape' => $this->shape,
            'label' => $this->label,
            'explanation' => $this->explanation,
            'favored' => $this->favored,
            'avoided' => $this->avoided,
        ];
    }
}

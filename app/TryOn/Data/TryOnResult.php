<?php

namespace App\TryOn\Data;

final readonly class TryOnResult
{
    /**
     * @param  string  $imageBytes  natija rasmi (PNG yoki JPEG bytes)
     * @param  string|null  $costUsd  decimal satr ko'rinishida ("0.0512"), float emas
     * @param  array<string, mixed>  $usage  provider qaytargan token statistikasi
     */
    public function __construct(
        public string $imageBytes,
        public string $model,
        public ?string $costUsd = null,
        public array $usage = [],
    ) {}
}

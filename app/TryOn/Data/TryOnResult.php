<?php

namespace App\TryOn\Data;

final readonly class TryOnResult
{
    /**
     * @param  string  $imageBytes  result image (PNG or JPEG bytes)
     * @param  string|null  $costUsd  decimal string ("0.0512"), never a float
     * @param  array<string, mixed>  $usage  token usage returned by the provider
     */
    public function __construct(
        public string $imageBytes,
        public string $model,
        public ?string $costUsd = null,
        public array $usage = [],
    ) {}
}

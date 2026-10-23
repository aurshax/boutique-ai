<?php

namespace App\TryOn\Data;

/**
 * Everything a provider needs. Images are passed as bytes, not paths:
 * a provider must not care where they come from.
 */
final readonly class TryOnInput
{
    /**
     * @param  string  $personImage  customer photo (JPEG bytes, already sanitised)
     * @param  list<ReferenceImage>  $references  garment and fabric images (if any)
     * @param  string|null  $apiKey  customer's own OpenAI key (BYOK); null for the mock provider
     */
    public function __construct(
        public string $personImage,
        public array $references,
        public string $prompt,
        #[\SensitiveParameter] public ?string $apiKey = null,
        public string $quality = 'medium',
    ) {}
}

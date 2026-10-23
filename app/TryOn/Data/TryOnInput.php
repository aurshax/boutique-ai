<?php

namespace App\TryOn\Data;

/**
 * Provider'ga beriladigan hamma narsa. Rasmlar — fayl tarkibi (bytes), yo'l emas:
 * provider qayerdan kelganini bilmasligi kerak.
 */
final readonly class TryOnInput
{
    /**
     * @param  string  $personImage  mijoz rasmi (JPEG bytes, allaqachon tozalangan)
     * @param  list<ReferenceImage>  $references  kiyim va tkan rasmlari (bo'lsa)
     * @param  string|null  $apiKey  mijozning OpenAI kaliti (BYOK). Mock uchun null.
     */
    public function __construct(
        public string $personImage,
        public array $references,
        public string $prompt,
        #[\SensitiveParameter] public ?string $apiKey = null,
        public string $quality = 'medium',
    ) {}
}

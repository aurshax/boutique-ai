<?php

namespace App\Catalog;

use App\TryOn\Support\ImageCost;
use App\TryOn\Support\OpenAiErrors;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Matndan rasm (POST /v1/images/generations) — katalog uchun. Try-on'dagi kabi: kalit faqat sarlavhada,
 * qayta urinish yo'q, narx usage'dan.
 */
class OpenAiImageGenerator
{
    /**
     * @return array{bytes: string, cost_usd: string|null, usage: array<string, mixed>}
     *
     * @throws RuntimeException foydalanuvchiga ko'rsatsa bo'ladigan xabar bilan
     */
    public function generate(string $prompt, #[\SensitiveParameter] string $apiKey, string $model, string $quality, string $size = '1024x1024'): array
    {
        $config = config('tryon.openai');

        try {
            $response = Http::baseUrl((string) $config['base_url'])
                ->withToken($apiKey)
                ->acceptJson()
                ->timeout((int) $config['timeout'])
                ->connectTimeout((int) $config['connect_timeout'])
                ->post('/images/generations', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'quality' => $quality,
                    'size' => $size,
                    'output_format' => 'webp',
                    'output_compression' => 85,
                    'n' => 1,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('OpenAI bilan aloqa uzildi yoki javob kechikdi.');
        }

        if ($response->failed()) {
            throw new RuntimeException(OpenAiErrors::message($response, 'images/generations'));
        }

        $b64 = $response->json('data.0.b64_json');
        $bytes = is_string($b64) ? base64_decode($b64, true) : false;
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('OpenAI rasm qaytarmadi.');
        }

        $usage = (array) $response->json('usage', []);

        return [
            'bytes' => $bytes,
            'cost_usd' => isset($config['pricing'][$model]) ? ImageCost::fromUsage($usage, $config['pricing'][$model]) : null,
            'usage' => $usage,
        ];
    }
}

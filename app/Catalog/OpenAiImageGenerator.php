<?php

namespace App\Catalog;

use App\TryOn\Support\ImageCost;
use App\TryOn\Support\OpenAiErrors;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Text-to-image (POST /v1/images/generations) for the catalogue. Same rules as the try-on:
 * key only in the header, no retries, cost from `usage`.
 */
class OpenAiImageGenerator
{
    /**
     * @return array{bytes: string, cost_usd: string|null, usage: array<string, mixed>}
     *
     * @throws RuntimeException with a user-facing message; code 1 = fatal for the whole batch (bad key, no quota)
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
            throw new RuntimeException(__('tryon.openai.connection'));
        }

        if ($response->failed()) {
            throw new RuntimeException(OpenAiErrors::message($response, 'images/generations'), OpenAiErrors::isFatal($response) ? 1 : 0);
        }

        $b64 = $response->json('data.0.b64_json');
        $bytes = is_string($b64) ? base64_decode($b64, true) : false;
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException(__('tryon.openai.no_image'));
        }

        $usage = (array) $response->json('usage', []);

        return [
            'bytes' => $bytes,
            'cost_usd' => isset($config['pricing'][$model]) ? ImageCost::fromUsage($usage, $config['pricing'][$model]) : null,
            'usage' => $usage,
        ];
    }
}

<?php

namespace App\TryOn\Providers;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Data\TryOnInput;
use App\TryOn\Data\TryOnResult;
use App\TryOn\Exceptions\TryOnProviderException;
use App\TryOn\Support\ImageCost;
use App\TryOn\Support\OpenAiErrors;
use App\TryOn\Support\OutputSize;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI Images API (edit): customer photo + garment/fabric images + prompt → one result image.
 *
 * - The key belongs to the customer (BYOK): TryOnInput->apiKey, sent only in the Authorization header.
 *   It never reaches logs, exceptions or the database. OpenAI's error text is not logged either
 *   (it may contain part of the key).
 * - NO retries: every attempt costs the customer money (same rule as TryOnJob::$tries = 1).
 * - Cost comes from the response `usage` (ImageCost), with per-model prices in config('tryon.openai.pricing').
 * - Output size keeps the input aspect ratio (OutputSize), so framing and pose stay put.
 */
class OpenAiProvider implements TryOnProvider
{
    /**
     * @param  string|null  $model  overrides the configured model (used by try-on:compare)
     * @param  string|null  $inputFidelity  'high' | 'low' — supported by gpt-image-1 / 1.5 only
     */
    public function __construct(
        private readonly ?string $model = null,
        private readonly ?string $inputFidelity = null,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function generate(TryOnInput $input): TryOnResult
    {
        if ($input->apiKey === null || $input->apiKey === '') {
            throw new TryOnProviderException(__('tryon.openai.missing_key'));
        }

        $config = config('tryon.openai');
        $model = $this->model ?? (string) $config['model'];
        $fidelity = $this->inputFidelity ?? $config['input_fidelity'];
        [$width, $height] = @getimagesizefromstring($input->personImage) ?: [0, 0];

        try {
            $response = Http::baseUrl((string) $config['base_url'])
                ->withToken($input->apiKey)
                ->acceptJson()
                ->timeout((int) $config['timeout'])
                ->connectTimeout((int) $config['connect_timeout'])
                ->post('/images/edits', array_filter([
                    'model' => $model,
                    'prompt' => $input->prompt,
                    'images' => $this->images($input),
                    'quality' => $input->quality,
                    'size' => OutputSize::for($model, $width, $height, (int) $config['output_longest_side']),
                    'input_fidelity' => $fidelity,          // null → omitted (the gpt-image-2 family rejects it)
                    'output_format' => 'jpeg',
                    'output_compression' => (int) $config['output_compression'],
                    'n' => 1,
                ], fn ($value) => $value !== null));
        } catch (ConnectionException) {
            throw new TryOnProviderException(__('tryon.openai.connection'));
        }

        if ($response->failed()) {
            throw new TryOnProviderException(OpenAiErrors::message($response, 'images/edits'));
        }

        $b64 = $response->json('data.0.b64_json');
        $bytes = is_string($b64) ? base64_decode($b64, true) : false;
        if ($bytes === false || $bytes === '') {
            throw new TryOnProviderException(__('tryon.openai.no_image'));
        }

        $usage = (array) $response->json('usage', []);

        return new TryOnResult(
            imageBytes: $bytes,
            model: $model,
            costUsd: isset($config['pricing'][$model]) ? ImageCost::fromUsage($usage, $config['pricing'][$model]) : null,
            usage: $usage,
        );
    }

    /**
     * Image 1 is the customer, then garment/fabric images in PromptBuilder order ("see image N" relies on it).
     *
     * @return list<array{image_url: string}>
     */
    private function images(TryOnInput $input): array
    {
        $images = [['image_url' => $this->dataUrl($input->personImage)]];
        foreach ($input->references as $reference) {
            $images[] = ['image_url' => $this->dataUrl($reference->bytes)];
        }

        return $images;
    }

    private function dataUrl(string $bytes): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'image/jpeg';

        return "data:{$mime};base64,".base64_encode($bytes);
    }
}

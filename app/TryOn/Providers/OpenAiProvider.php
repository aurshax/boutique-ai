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
 * OpenAI Images API (edit): mijoz rasmi + kiyim/tkan rasmlari + prompt → bitta natija rasmi.
 *
 * - Kalit mijozniki (BYOK): TryOnInput->apiKey, faqat Authorization sarlavhasida. Log'ga, exception'ga,
 *   DB'ga tushmaydi. OpenAI xato matni ham log'ga yozilmaydi (unda kalitning bir qismi bo'lishi mumkin).
 * - Qayta urinish YO'Q: har urinish — mijozdan pul (TryOnJob::$tries = 1 bilan bir xil qoida).
 * - Narx javobdagi `usage` dan hisoblanadi (ImageCost), model bo'yicha narx — config('tryon.openai.pricing').
 * - Natija o'lchami kirish rasmi nisbatida (OutputSize) — kadr va poza saqlansin.
 */
class OpenAiProvider implements TryOnProvider
{
    /**
     * @param  string|null  $model  config'dagi modelni almashtirish (try-on:compare buyrug'i uchun)
     * @param  string|null  $inputFidelity  'high' | 'low' — faqat gpt-image-1 / 1.5 qo'llaydi
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
            throw new TryOnProviderException('OpenAI API kaliti berilmagan.');
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
                    'input_fidelity' => $fidelity,          // null → yuborilmaydi (gpt-image-2 oilasi qabul qilmaydi)
                    'output_format' => 'jpeg',
                    'output_compression' => (int) $config['output_compression'],
                    'n' => 1,
                ], fn ($value) => $value !== null));
        } catch (ConnectionException) {
            throw new TryOnProviderException('OpenAI bilan aloqa uzildi yoki javob kechikdi. Birozdan keyin qayta urinib ko\'ring.');
        }

        if ($response->failed()) {
            throw new TryOnProviderException(OpenAiErrors::message($response, 'images/edits'));
        }

        $b64 = $response->json('data.0.b64_json');
        $bytes = is_string($b64) ? base64_decode($b64, true) : false;
        if ($bytes === false || $bytes === '') {
            throw new TryOnProviderException('OpenAI rasm qaytarmadi.');
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
     * 1-rasm — mijoz, keyin PromptBuilder tartibidagi kiyim/tkan rasmlari ("see image N" shu tartibga tayanadi).
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

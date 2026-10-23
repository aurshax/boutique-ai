<?php

namespace App\TryOn\Providers;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Data\TryOnInput;
use App\TryOn\Data\TryOnResult;
use App\TryOn\Exceptions\TryOnProviderException;
use App\TryOn\Support\ImageCost;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Images API (edit): mijoz rasmi + kiyim/tkan rasmlari + prompt → bitta natija rasmi.
 *
 * - Kalit mijozniki (BYOK): TryOnInput->apiKey, faqat Authorization sarlavhasida. Log'ga, exception'ga,
 *   DB'ga tushmaydi. OpenAI xato matni ham log'ga yozilmaydi (unda kalitning bir qismi bo'lishi mumkin).
 * - Qayta urinish YO'Q: har urinish — mijozdan pul (TryOnJob::$tries = 1 bilan bir xil qoida).
 * - Narx javobdagi `usage` dan hisoblanadi (ImageCost).
 */
class OpenAiProvider implements TryOnProvider
{
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
        $model = (string) $config['model'];

        try {
            $response = Http::baseUrl((string) $config['base_url'])
                ->withToken($input->apiKey)
                ->acceptJson()
                ->timeout((int) $config['timeout'])
                ->connectTimeout((int) $config['connect_timeout'])
                ->post('/images/edits', [
                    'model' => $model,
                    'prompt' => $input->prompt,
                    'images' => $this->images($input),
                    'quality' => $input->quality,
                    'size' => (string) $config['size'],
                    'output_format' => 'jpeg',
                    'output_compression' => (int) $config['output_compression'],
                    'n' => 1,
                ]);
        } catch (ConnectionException) {
            throw new TryOnProviderException('OpenAI bilan aloqa uzildi yoki javob kechikdi. Birozdan keyin qayta urinib ko\'ring.');
        }

        if ($response->failed()) {
            throw new TryOnProviderException($this->errorMessage($response));
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
            costUsd: ImageCost::fromUsage($usage, $config['pricing']),
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

    /** OpenAI xatosi → foydalanuvchiga tushunarli o'zbekcha xabar. Xom matn qaytarilmaydi. */
    private function errorMessage(Response $response): string
    {
        $code = (string) $response->json('error.code', '');
        $type = (string) $response->json('error.type', '');

        // Faqat status/kod/tur — xabar matni emas.
        Log::warning('OpenAI images/edits xatosi', ['status' => $response->status(), 'code' => $code, 'type' => $type]);

        return match (true) {
            $response->status() === 401 => 'OpenAI kaliti noto\'g\'ri yoki o\'chirilgan. Kalitni tekshirib, qayta kiriting.',
            $code === 'insufficient_quota', $code === 'billing_hard_limit_reached' => 'OpenAI hisobingizda mablag\' yoki limit tugagan. platform.openai.com → Billing / Limits.',
            $response->status() === 429 => 'OpenAI so\'rovlar chegarasiga yetdi. Bir daqiqadan keyin qayta urinib ko\'ring.',
            $response->status() === 403 => 'Kalitga bu modeldan foydalanishga ruxsat yo\'q (OpenAI tashkilot tasdiqlashi yoki project ruxsatlari kerak bo\'lishi mumkin).',
            $code === 'moderation_blocked', $code === 'content_policy_violation' => 'OpenAI bu rasmni xavfsizlik qoidalari bo\'yicha qayta ishlamadi. Boshqa rasm bilan urinib ko\'ring.',
            $response->status() === 400 => 'OpenAI so\'rovni qabul qilmadi. Boshqa rasm bilan urinib ko\'ring.',
            $response->serverError() => 'OpenAI serverida vaqtinchalik xato. Birozdan keyin qayta urinib ko\'ring.',
            default => 'OpenAI xatosi ('.$response->status().').',
        };
    }
}

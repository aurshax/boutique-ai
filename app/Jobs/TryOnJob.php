<?php

namespace App\Jobs;

use App\Enums\TryOnStatus;
use App\Models\TryOnRequest;
use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Data\TryOnInput;
use App\TryOn\Exceptions\InvalidImageException;
use App\TryOn\Exceptions\TryOnProviderException;
use App\TryOn\ImageService;
use App\TryOn\PromptBuilder;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bitta try-on so'rovini fonda bajaradi.
 *
 * Xavfsizlik:
 *  - ShouldBeEncrypted: jobs jadvalidagi payload (ichida mijozning OpenAI kaliti) APP_KEY bilan shifrlanadi.
 *  - #[\SensitiveParameter]: exception stack trace'larida kalit ko'rinmaydi.
 *  - $tries = 1: qayta urinish = mijozdan ikkinchi marta pul. Hech qachon avtomatik takrorlanmaydi.
 *  - Atomar "queued → running" o'tishi: ikki worker bitta so'rovni ikki marta bajarmaydi.
 */
class TryOnJob implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $requestId,
        #[\SensitiveParameter] public ?string $apiKey = null,
    ) {
        $this->timeout = (int) config('tryon.job.timeout', 180);
    }

    public function handle(TryOnProvider $provider, PromptBuilder $prompts, ImageService $images): void
    {
        // Atomar o'tish: faqat 'queued' bo'lsa 'running' ga o'tkazamiz. 0 qator = kimdir allaqachon olgan.
        $claimed = TryOnRequest::whereKey($this->requestId)
            ->where('status', TryOnStatus::Queued->value)
            ->update(['status' => TryOnStatus::Running->value, 'started_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $request = TryOnRequest::with(['look.products', 'fabric'])->findOrFail($this->requestId);

        try {
            if ($request->look === null || $request->upload_path === null) {
                throw new TryOnProviderException('So\'rovda look yoki rasm yo\'q.');
            }

            $built = $prompts->build($request->look, $request->fabric, $request->height_cm);

            $result = $provider->generate(new TryOnInput(
                personImage: $images->read($request->upload_path),
                references: $built['references'],
                prompt: $built['prompt'],
                apiKey: $this->apiKey,
                quality: (string) ($request->params['quality'] ?? config('tryon.openai.quality')),
            ));

            $resultPath = $images->storeResult($request->id, $result->imageBytes);

            $request->update([
                'status' => TryOnStatus::Done,
                'result_path' => $resultPath,
                'model' => $result->model,
                'cost_usd' => $result->costUsd,
                'params' => [...$request->params, 'usage' => $result->usage],
                'finished_at' => now(),
            ]);
        } catch (TryOnProviderException|InvalidImageException $e) {
            // Kutilgan xato — foydalanuvchiga ko'rsatsa bo'ladigan xabar.
            $this->markFailed($request, $e->getMessage());
        } catch (Throwable $e) {
            // Kutilmagan xato — foydalanuvchiga umumiy xabar, logga tafsilot (kalitsiz).
            Log::error('TryOnJob kutilmagan xato', [
                'request_id' => $this->requestId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $this->markFailed($request, 'Ichki xato. Qaytadan urinib ko\'ring.');
        }
    }

    /** Timeout yoki worker qulasa — Laravel shu metodni chaqiradi. */
    public function failed(?Throwable $e): void
    {
        $request = TryOnRequest::find($this->requestId);
        if ($request !== null && ! $request->status->isFinished()) {
            $this->markFailed($request, 'Vaqt tugadi. AI javob bermadi.');
        }
    }

    private function markFailed(TryOnRequest $request, string $message): void
    {
        $request->update([
            'status' => TryOnStatus::Failed,
            'error' => mb_substr($message, 0, 500),
            'finished_at' => now(),
        ]);
    }
}

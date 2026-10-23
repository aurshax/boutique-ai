<?php

namespace App\Jobs;

use App\Enums\TryOnStatus;
use App\Models\TryOnRequest;
use App\TryOn\Data\TryOnInput;
use App\TryOn\Exceptions\InvalidImageException;
use App\TryOn\Exceptions\TryOnProviderException;
use App\TryOn\ImageService;
use App\TryOn\PromptBuilder;
use App\TryOn\ProviderFactory;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one try-on request in the background.
 *
 * Security:
 *  - ShouldBeEncrypted: the jobs-table payload (which holds the customer's OpenAI key) is encrypted with APP_KEY.
 *  - #[\SensitiveParameter]: the key never shows up in exception stack traces.
 *  - $tries = 1: a retry would charge the customer twice, so it never retries automatically.
 *  - Atomic "queued → running" claim: two workers can never run the same request.
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

    public function handle(ProviderFactory $providers, PromptBuilder $prompts, ImageService $images): void
    {
        // Atomic claim: only a 'queued' row becomes 'running'. 0 rows = someone else already took it.
        $claimed = TryOnRequest::whereKey($this->requestId)
            ->where('status', TryOnStatus::Queued->value)
            ->update(['status' => TryOnStatus::Running->value, 'started_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $request = TryOnRequest::with(['look.products', 'fabric'])->findOrFail($this->requestId);

        try {
            if ($request->look === null || $request->upload_path === null) {
                throw new TryOnProviderException(__('tryon.errors.request_incomplete'));
            }

            $built = $prompts->build($request->look, $request->fabric, $request->height_cm);

            $result = $providers->make($this->apiKey)->generate(new TryOnInput(
                personImage: $images->read($request->upload_path),
                references: $built['references'],
                prompt: $built['prompt'],
                apiKey: $this->apiKey,
                quality: (string) ($request->params['quality'] ?? config('tryon.openai.quality')),
            ));

            $resultPath = $images->storeResult($request->id, $result->imageBytes);

            // Atomic finish: if the customer pressed "stop" / "delete" meanwhile (status = cancelled),
            // do not store the result and delete the file. The cost is still recorded — it was charged.
            $finished = TryOnRequest::whereKey($request->id)
                ->where('status', TryOnStatus::Running->value)
                ->update([
                    'status' => TryOnStatus::Done->value,
                    'result_path' => $resultPath,
                    'model' => $result->model,
                    'cost_usd' => $result->costUsd,
                    'params' => json_encode([...$request->params, 'usage' => $result->usage], JSON_THROW_ON_ERROR),
                    'finished_at' => now(),
                ]);

            if ($finished === 0) {
                $images->delete($resultPath);
                TryOnRequest::whereKey($request->id)->update(['model' => $result->model, 'cost_usd' => $result->costUsd]);
            }
        } catch (TryOnProviderException|InvalidImageException $e) {
            // Expected error — the message is safe to show to the customer.
            $this->markFailed($request, $e->getMessage());
        } catch (Throwable $e) {
            // Unexpected error — generic message for the customer, details (without the key) go to the log.
            Log::error('TryOnJob unexpected error', [
                'request_id' => $this->requestId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $this->markFailed($request, __('tryon.errors.internal'));
        }
    }

    /** Called by Laravel on timeout or when the worker dies. */
    public function failed(?Throwable $e): void
    {
        $request = TryOnRequest::find($this->requestId);
        if ($request !== null && ! $request->status->isFinished()) {
            $this->markFailed($request, __('tryon.errors.timeout'));
        }
    }

    /** Fails only a request that is still in progress (never overwrites cancelled). */
    private function markFailed(TryOnRequest $request, string $message): void
    {
        TryOnRequest::whereKey($request->id)
            ->whereIn('status', [TryOnStatus::Queued->value, TryOnStatus::Running->value])
            ->update([
                'status' => TryOnStatus::Failed->value,
                'error' => mb_substr($message, 0, 500),
                'finished_at' => now(),
            ]);
    }
}

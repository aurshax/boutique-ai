<?php

namespace App\Console\Commands;

use App\Models\TryOnRequest;
use App\TryOn\Data\TryOnInput;
use App\TryOn\Exceptions\TryOnProviderException;
use App\TryOn\ImageService;
use App\TryOn\PromptBuilder;
use App\TryOn\Providers\OpenAiProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Spike tool: run several models side by side on one existing request's photo and look.
 * Results go to storage/app/private/compare/{request_id}/. EVERY MODEL COSTS REAL MONEY.
 *
 *   sail artisan try-on:compare {id} --model=gpt-image-2.5-sunburst --model=gpt-image-1.5:high
 *
 * The key is never an argument (it would stay in shell history) — it is asked for as a hidden secret.
 */
class CompareTryOnModels extends Command
{
    protected $signature = 'try-on:compare
        {request : try_on_requests.id ("latest" for the most recent)}
        {--model=* : model or model:input_fidelity, e.g. gpt-image-1.5:high}
        {--quality=medium : low|medium|high}';

    protected $description = 'Compare several OpenAI models on one try-on request (paid)';

    public function handle(PromptBuilder $prompts, ImageService $images): int
    {
        $request = $this->argument('request') === 'latest'
            ? TryOnRequest::with(['look.products', 'fabric'])->whereNotNull('upload_path')->latest()->first()
            : TryOnRequest::with(['look.products', 'fabric'])->find($this->argument('request'));

        if ($request === null || $request->upload_path === null || $request->look === null) {
            $this->error('Request not found or its photo was deleted (photos are kept for 24 hours).');

            return self::FAILURE;
        }

        $models = $this->option('model') ?: [(string) config('tryon.openai.model')];
        $quality = (string) $this->option('quality');

        $this->warn(sprintf('%d generation(s), quality: %s — each one is billed to your OpenAI account.', count($models), $quality));
        $key = (string) $this->secret('OpenAI API key');
        if (! str_starts_with($key, 'sk-')) {
            $this->error('The key must start with sk-.');

            return self::FAILURE;
        }

        $built = $prompts->build($request->look, $request->fabric, $request->height_cm);
        $person = $images->read($request->upload_path);
        $dir = "compare/{$request->id}";
        Storage::disk('local')->put("{$dir}/0-original.jpg", $person);   // dev tool: always the local disk

        $rows = [];
        foreach ($models as $spec) {
            [$model, $fidelity] = array_pad(explode(':', $spec, 2), 2, null);
            $this->line("→ {$spec} ...");
            $started = microtime(true);

            try {
                $result = (new OpenAiProvider($model, $fidelity))->generate(new TryOnInput(
                    personImage: $person,
                    references: $built['references'],
                    prompt: $built['prompt'],
                    apiKey: $key,
                    quality: $quality,
                ));
            } catch (TryOnProviderException $e) {
                $rows[] = [$spec, 'ERROR', '-', '-', $e->getMessage()];

                continue;
            }

            $file = sprintf('%s/%s-%s.jpg', $dir, str_replace(':', '_', $spec), $quality);
            Storage::disk('local')->put($file, $result->imageBytes);
            $rows[] = [
                $spec,
                round(microtime(true) - $started).' s',
                $result->costUsd !== null ? '$'.$result->costUsd : '?',
                ($result->usage['input_tokens'] ?? '?').' / '.($result->usage['output_tokens'] ?? '?'),
                'storage/app/private/'.$file,
            ];
        }

        $this->table(['Model', 'Time', 'Cost', 'Tokens (in / out)', 'File'], $rows);

        return self::SUCCESS;
    }
}

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
 * Spike uchun: bitta mavjud so'rovning rasmi va look'i bilan bir nechta modelni yonma-yon sinash.
 * Natijalar storage/app/private/compare/{request_id}/ ga yoziladi. HAR MODEL — HAQIQIY PUL.
 *
 *   sail artisan try-on:compare {id} --model=gpt-image-2.5-sunburst --model=gpt-image-1.5:high
 *
 * Kalit argument sifatida berilmaydi (shell tarixida qolmasin) — yashirin so'raladi.
 */
class CompareTryOnModels extends Command
{
    protected $signature = 'try-on:compare
        {request : try_on_requests.id (oxirgisi uchun "latest")}
        {--model=* : model yoki model:input_fidelity, masalan gpt-image-1.5:high}
        {--quality=medium : low|medium|high}';

    protected $description = 'Bitta try-on so\'rovini bir nechta OpenAI modelida solishtirish (pullik)';

    public function handle(PromptBuilder $prompts, ImageService $images): int
    {
        $request = $this->argument('request') === 'latest'
            ? TryOnRequest::with(['look.products', 'fabric'])->whereNotNull('upload_path')->latest()->first()
            : TryOnRequest::with(['look.products', 'fabric'])->find($this->argument('request'));

        if ($request === null || $request->upload_path === null || $request->look === null) {
            $this->error('So\'rov topilmadi yoki uning rasmi o\'chirilgan (24 soatdan keyin o\'chadi).');

            return self::FAILURE;
        }

        $models = $this->option('model') ?: [(string) config('tryon.openai.model')];
        $quality = (string) $this->option('quality');

        $this->warn(sprintf('%d ta generatsiya, sifat: %s — har biri OpenAI hisobingizdan to\'lanadi.', count($models), $quality));
        $key = (string) $this->secret('OpenAI API kaliti');
        if (! str_starts_with($key, 'sk-')) {
            $this->error('Kalit sk- bilan boshlanishi kerak.');

            return self::FAILURE;
        }

        $built = $prompts->build($request->look, $request->fabric, $request->height_cm);
        $person = $images->read($request->upload_path);
        $dir = "compare/{$request->id}";
        Storage::disk('local')->put("{$dir}/0-original.jpg", $person);

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
                $rows[] = [$spec, 'XATO', '-', '-', $e->getMessage()];

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

        $this->table(['Model', 'Vaqt', 'Narx', 'Tokenlar (kirish / chiqish)', 'Fayl'], $rows);

        return self::SUCCESS;
    }
}

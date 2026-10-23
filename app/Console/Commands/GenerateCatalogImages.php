<?php

namespace App\Console\Commands;

use App\Catalog\CatalogImagePrompts;
use App\Catalog\OpenAiImageGenerator;
use App\Models\Fabric;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Generates catalogue images with AI (one-off and PAID — with your key, not a customer's):
 *  - products → public/media/catalog/{slug}.webp  (products.image)
 *  - fabrics  → public/media/fabrics/swatch-{slug}.webp  (fabrics.swatch_image)
 * Existing files are skipped (unless --force), so re-running after an interruption is safe.
 *
 *   sail artisan catalog:generate-images --dry-run
 *   sail artisan catalog:generate-images --only=blazer-olive-tweed --force
 */
class GenerateCatalogImages extends Command
{
    protected $signature = 'catalog:generate-images
        {--only=* : only these slug(s)}
        {--products : products only}
        {--fabrics : fabrics only}
        {--force : regenerate existing images}
        {--quality=medium : low|medium|high}
        {--model=gpt-image-2.5-flare : fast everyday generation model}
        {--dry-run : list files and prompts without calling the API}';

    protected $description = 'Generate catalogue product images and fabric swatches with OpenAI (paid)';

    public function handle(OpenAiImageGenerator $generator): int
    {
        $base = (string) config('tryon.catalog_media_path', public_path());
        $jobs = $this->jobs();

        $todo = array_values(array_filter($jobs, fn (array $job) => $this->option('force') || ! is_file("{$base}/{$job['path']}")));
        if ($todo === []) {
            $this->info('All images exist. Use --force to regenerate.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($todo as $job) {
                $this->line("<info>{$job['path']}</info>\n  {$job['prompt']}");
            }
            $this->info(count($todo).' image(s) will be generated.');

            return self::SUCCESS;
        }

        $model = $this->stringOption('model');
        $quality = $this->stringOption('quality');
        $this->warn(sprintf('%d image(s), model: %s, quality: %s — billed to your OpenAI account.', count($todo), $model, $quality));
        $key = (string) $this->secret('OpenAI API key');
        if (! str_starts_with($key, 'sk-')) {
            $this->error('The key must start with sk-.');

            return self::FAILURE;
        }

        $total = 0.0;
        $failed = 0;
        $bar = $this->output->createProgressBar(count($todo));
        foreach ($todo as $job) {
            try {
                $result = $generator->generate($job['prompt'], $key, $model, $quality);
            } catch (RuntimeException $e) {
                $failed++;
                $this->newLine();
                $this->error("{$job['path']}: {$e->getMessage()}");
                if ($e->getCode() === 1) {
                    return self::FAILURE;   // bad key / no quota — every next call would fail too, stop now
                }

                continue;
            }

            File::ensureDirectoryExists(dirname("{$base}/{$job['path']}"));
            File::put("{$base}/{$job['path']}", $result['bytes']);
            $job['model']->forceFill([$job['column'] => $job['path']])->save();
            $total += (float) ($result['cost_usd'] ?? 0);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf('Done: %d, failed: %d, total ≈ $%.4f', count($todo) - $failed, $failed, $total));
        $this->line('Note: re-running the seeder keeps these image paths (as long as the files exist).');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return list<array{path: string, prompt: string, model: Product|Fabric, column: string}> */
    private function jobs(): array
    {
        $only = (array) $this->option('only');
        $wantProducts = ! $this->option('fabrics') || $this->option('products');
        $wantFabrics = ! $this->option('products') || $this->option('fabrics');
        $jobs = [];

        if ($wantProducts) {
            foreach (Product::with('defaultFabric')->orderBy('id')->get() as $product) {
                if ($only === [] || in_array($product->slug, $only, true)) {
                    $jobs[] = ['path' => "media/catalog/{$product->slug}.webp", 'prompt' => CatalogImagePrompts::product($product), 'model' => $product, 'column' => 'image'];
                }
            }
        }

        if ($wantFabrics) {
            foreach (Fabric::orderBy('id')->get() as $fabric) {
                if ($only === [] || in_array($fabric->slug, $only, true)) {
                    $jobs[] = ['path' => "media/fabrics/swatch-{$fabric->slug}.webp", 'prompt' => CatalogImagePrompts::fabric($fabric), 'model' => $fabric, 'column' => 'swatch_image'];
                }
            }
        }

        return $jobs;
    }

    /** A value option as a string (Symfony types options as mixed). */
    private function stringOption(string $name): string
    {
        $value = $this->option($name);

        return is_string($value) ? $value : '';
    }
}

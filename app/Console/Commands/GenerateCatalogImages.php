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
 * Katalog rasmlarini AI bilan yaratadi (bir martalik, PULLIK — mijoz emas, sizning kalitingiz):
 *  - mahsulotlar → public/media/catalog/{slug}.webp  (products.image)
 *  - tkanlar     → public/media/fabrics/swatch-{slug}.webp  (fabrics.swatch_image)
 * Bor fayllar o'tkazib yuboriladi (--force bo'lmasa) — to'xtab qolsa, qayta ishga tushirish xavfsiz.
 *
 *   sail artisan catalog:generate-images --dry-run
 *   sail artisan catalog:generate-images --only=blazer-olive-tweed --force
 */
class GenerateCatalogImages extends Command
{
    protected $signature = 'catalog:generate-images
        {--only=* : faqat shu slug(lar)}
        {--products : faqat mahsulotlar}
        {--fabrics : faqat tkanlar}
        {--force : bor rasmni qayta yaratish}
        {--quality=medium : low|medium|high}
        {--model=gpt-image-2.5-flare : tez, kundalik generatsiya uchun model}
        {--dry-run : so\'rov yubormasdan ro\'yxat va promptlarni ko\'rsatish}';

    protected $description = 'Katalog mahsulot rasmlari va tkan namunalarini OpenAI bilan yaratish (pullik)';

    public function handle(OpenAiImageGenerator $generator): int
    {
        $base = (string) config('tryon.catalog_media_path', public_path());
        $jobs = $this->jobs();

        $todo = array_values(array_filter($jobs, fn (array $job) => $this->option('force') || ! is_file("{$base}/{$job['path']}")));
        if ($todo === []) {
            $this->info('Hamma rasmlar bor. Qayta yaratish uchun --force.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($todo as $job) {
                $this->line("<info>{$job['path']}</info>\n  {$job['prompt']}");
            }
            $this->info(count($todo).' ta rasm yaratiladi.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d ta rasm, model: %s, sifat: %s — OpenAI hisobingizdan to\'lanadi.', count($todo), $this->option('model'), $this->option('quality')));
        $key = (string) $this->secret('OpenAI API kaliti');
        if (! str_starts_with($key, 'sk-')) {
            $this->error('Kalit sk- bilan boshlanishi kerak.');

            return self::FAILURE;
        }

        $total = 0.0;
        $failed = 0;
        $bar = $this->output->createProgressBar(count($todo));
        foreach ($todo as $job) {
            try {
                $result = $generator->generate($job['prompt'], $key, (string) $this->option('model'), (string) $this->option('quality'));
            } catch (RuntimeException $e) {
                $failed++;
                $this->newLine();
                $this->error("{$job['path']}: {$e->getMessage()}");
                if (str_contains($e->getMessage(), 'kaliti') || str_contains($e->getMessage(), 'limit')) {
                    return self::FAILURE;   // kalit/limit xatosi — qolganlari ham yiqiladi, pul sarflamay to'xtaymiz
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

        $this->info(sprintf('Tayyor: %d ta, xato: %d ta, jami ≈ $%.4f', count($todo) - $failed, $failed, $total));
        $this->line('Eslatma: seeder qayta ishga tushsa ham rasm yo\'llari saqlanadi (fayl bor bo\'lsa).');

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
}

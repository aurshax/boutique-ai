<?php

namespace Tests\Feature\TryOn;

use App\Models\Fabric;
use App\Models\Look;
use App\TryOn\PromptBuilder;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_prompt_contains_look_items_fabric_and_height(): void
    {
        $this->seed(CatalogSeeder::class);

        $look = Look::where('slug', 'smart-navy-knit')->firstOrFail();
        $fabric = Fabric::where('slug', 'grey-check')->firstOrFail();

        $built = (new PromptBuilder)->build($look, $fabric, 178);

        $this->assertStringContainsString('178 cm', $built['prompt']);
        $this->assertStringContainsString('Kulrang katak', $built['prompt']);
        $this->assertStringContainsString('trousers:', $built['prompt']);
        $this->assertStringContainsString('Keep his face', $built['prompt']);
        // Mahsulot rasmlari hali yo'q (matnli tavsif); kulrang katak tkanining swatch rasmi bor bo'lsa — u reference.
        $swatchExists = is_file(public_path('media/fabrics/fabric-navy-check-grey-twill.webp'));
        $this->assertCount($swatchExists ? 1 : 0, $built['references']);
        if ($swatchExists) {
            $this->assertSame('fabric swatch', $built['references'][0]->label);
            $this->assertStringContainsString('fabric swatch in image 2', $built['prompt']);
        }
    }
}

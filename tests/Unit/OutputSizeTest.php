<?php

namespace Tests\Unit;

use App\TryOn\Support\OutputSize;
use PHPUnit\Framework\TestCase;

class OutputSizeTest extends TestCase
{
    public function test_keeps_input_aspect_for_gpt_image_2_family(): void
    {
        $this->assertSame('1152x1536', OutputSize::for('gpt-image-2.5-sunburst', 768, 1024));   // 3:4 telefon rasmi
        $this->assertSame('1024x1536', OutputSize::for('gpt-image-2.5-sunburst', 1024, 1536));
        $this->assertSame('1536x1152', OutputSize::for('gpt-image-2', 1024, 768));
    }

    public function test_clamps_extreme_ratios_and_rounds_to_16(): void
    {
        $this->assertSame('512x1536', OutputSize::for('gpt-image-2.5-sunburst', 100, 1000));    // 1:10 → 1:3
        [$w, $h] = array_map('intval', explode('x', OutputSize::for('gpt-image-2.5-sunburst', 999, 1333)));
        $this->assertSame(0, $w % 16);
        $this->assertSame(0, $h % 16);
    }

    public function test_older_models_get_nearest_standard_size(): void
    {
        $this->assertSame('1024x1536', OutputSize::for('gpt-image-1.5', 768, 1024));
        $this->assertSame('1024x1024', OutputSize::for('gpt-image-1.5', 1000, 1000));
        $this->assertSame('1536x1024', OutputSize::for('gpt-image-1', 1600, 900));
    }
}

<?php

namespace Tests\Unit;

use App\TryOn\ProviderFactory;
use App\TryOn\Providers\MockProvider;
use App\TryOn\Providers\OpenAiProvider;
use InvalidArgumentException;
use Tests\TestCase;

class ProviderFactoryTest extends TestCase
{
    private const KEY = 'sk-test-FACTORY-KEY-1234567890abcdef';

    public function test_mock_mode_never_calls_openai(): void
    {
        config(['tryon.provider' => 'mock']);
        $factory = new ProviderFactory;

        $this->assertInstanceOf(MockProvider::class, $factory->make(self::KEY));
        $this->assertSame('none', $factory->keyMode());
    }

    public function test_openai_mode_always_uses_openai(): void
    {
        config(['tryon.provider' => 'openai']);
        $factory = new ProviderFactory;

        $this->assertInstanceOf(OpenAiProvider::class, $factory->make(null));
        $this->assertSame('required', $factory->keyMode());
    }

    public function test_auto_mode_uses_openai_only_with_a_visitor_key(): void
    {
        config(['tryon.provider' => 'auto']);
        $factory = new ProviderFactory;

        $this->assertInstanceOf(MockProvider::class, $factory->make(null));
        $this->assertInstanceOf(MockProvider::class, $factory->make(''));
        $this->assertInstanceOf(OpenAiProvider::class, $factory->make(self::KEY));
        $this->assertSame('optional', $factory->keyMode());
    }

    public function test_unknown_mode_fails_loudly(): void
    {
        config(['tryon.provider' => 'gemini']);

        $this->expectException(InvalidArgumentException::class);
        (new ProviderFactory)->make(null);
    }
}

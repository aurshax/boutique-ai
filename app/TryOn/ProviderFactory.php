<?php

namespace App\TryOn;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Providers\MockProvider;
use App\TryOn\Providers\OpenAiProvider;
use InvalidArgumentException;

/**
 * Picks the provider for one request from config('tryon.provider'):
 *  - mock   — always the free MockProvider (development);
 *  - openai — always OpenAI, the customer must bring a key;
 *  - auto   — public demo: OpenAI when the visitor supplied their own key, otherwise the mock.
 *             Nobody can spend the site owner's money.
 */
class ProviderFactory
{
    public function make(#[\SensitiveParameter] ?string $apiKey): TryOnProvider
    {
        return match ($this->mode()) {
            'mock' => new MockProvider,
            'openai' => new OpenAiProvider,
            'auto' => $apiKey !== null && $apiKey !== '' ? new OpenAiProvider : new MockProvider,
        };
    }

    /** Name stored with the request before the job runs. */
    public function nameFor(#[\SensitiveParameter] ?string $apiKey): string
    {
        return $this->make($apiKey)->name();
    }

    /** 'required' (openai), 'optional' (auto) or 'none' (mock) — drives the key form in the UI. */
    public function keyMode(): string
    {
        return match ($this->mode()) {
            'openai' => 'required',
            'auto' => 'optional',
            'mock' => 'none',
        };
    }

    /** @return 'mock'|'openai'|'auto' */
    private function mode(): string
    {
        $mode = (string) config('tryon.provider');
        if (! in_array($mode, ['mock', 'openai', 'auto'], true)) {
            throw new InvalidArgumentException("Unknown TRYON_PROVIDER: {$mode}");
        }

        return $mode;
    }
}

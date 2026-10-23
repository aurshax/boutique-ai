<?php

namespace App\TryOn\Contracts;

use App\TryOn\Data\TryOnInput;
use App\TryOn\Data\TryOnResult;
use App\TryOn\Exceptions\TryOnProviderException;

/**
 * AI provider shartnomasi. Kod faqat shu interfeysni biladi:
 * MockProvider (development) yoki OpenAiProvider (11-task) config orqali almashtiriladi.
 */
interface TryOnProvider
{
    /** @throws TryOnProviderException */
    public function generate(TryOnInput $input): TryOnResult;

    /** usage_log va try_on_requests.provider uchun qisqa nom. */
    public function name(): string;
}

<?php

namespace App\TryOn\Contracts;

use App\TryOn\Data\TryOnInput;
use App\TryOn\Data\TryOnResult;
use App\TryOn\Exceptions\TryOnProviderException;

/**
 * AI provider contract. The rest of the code only knows this interface:
 * MockProvider (free demo) and OpenAiProvider are chosen by ProviderFactory.
 */
interface TryOnProvider
{
    /** @throws TryOnProviderException */
    public function generate(TryOnInput $input): TryOnResult;

    /** Short name stored in try_on_requests.provider. */
    public function name(): string;
}

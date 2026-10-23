<?php

namespace App\TryOn\Exceptions;

use RuntimeException;

/**
 * Provider error. The message may be shown to the customer, so it must NEVER
 * contain the API key or a raw provider response.
 */
class TryOnProviderException extends RuntimeException {}

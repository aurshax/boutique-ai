<?php

namespace App\TryOn\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Turns an OpenAI error response into a clear, user-facing message (lang/en/tryon.php → openai.*).
 * Only status/code/type are logged — never the message text (it may contain part of the key).
 * Shared by the try-on provider and the catalogue image generator.
 */
final class OpenAiErrors
{
    public static function message(Response $response, string $context): string
    {
        $code = (string) $response->json('error.code', '');
        $type = (string) $response->json('error.type', '');

        Log::warning("OpenAI {$context} error", ['status' => $response->status(), 'code' => $code, 'type' => $type]);

        $key = match (true) {
            $response->status() === 401 => 'invalid_key',
            in_array($code, ['insufficient_quota', 'billing_hard_limit_reached'], true) => 'no_quota',
            $response->status() === 429 => 'rate_limited',
            $response->status() === 403 => 'forbidden',
            in_array($code, ['moderation_blocked', 'content_policy_violation'], true) => 'moderation',
            $response->status() === 400 => 'bad_request',
            $response->serverError() => 'server',
            default => null,
        };

        return $key !== null
            ? __("tryon.openai.{$key}")
            : __('tryon.openai.unknown', ['status' => $response->status()]);
    }

    /** Errors that will repeat for every following call with the same key (stop a batch early, save money). */
    public static function isFatal(Response $response): bool
    {
        return in_array($response->status(), [401, 403], true)
            || in_array((string) $response->json('error.code', ''), ['insufficient_quota', 'billing_hard_limit_reached'], true);
    }
}

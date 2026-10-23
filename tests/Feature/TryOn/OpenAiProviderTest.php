<?php

namespace Tests\Feature\TryOn;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Data\ReferenceImage;
use App\TryOn\Data\TryOnInput;
use App\TryOn\Exceptions\TryOnProviderException;
use App\TryOn\Providers\OpenAiProvider;
use App\TryOn\Support\ImageCost;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** OpenAI'ga haqiqiy so'rov ketmaydi: Http::fake() — pul sarflanmaydi. */
class OpenAiProviderTest extends TestCase
{
    private const KEY = 'sk-test-SECRET-KEY-1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_sends_person_and_references_and_returns_image_with_cost(): void
    {
        Http::fake(['api.openai.com/v1/images/edits' => Http::response([
            'data' => [['b64_json' => base64_encode($this->jpeg())]],
            'usage' => [
                'input_tokens' => 1300,
                'input_tokens_details' => ['text_tokens' => 300, 'image_tokens' => 1000],
                'output_tokens' => 1500,
            ],
        ])]);

        $result = (new OpenAiProvider)->generate($this->input([
            new ReferenceImage($this->jpeg(), 'fabric swatch'),
        ]));

        $this->assertSame($this->jpeg(), $result->imageBytes);
        $this->assertSame(config('tryon.openai.model'), $result->model);
        // 300×$5 + 1000×$8 + 1500×$30 = 54 500 / 1M = $0.0545
        $this->assertSame('0.0545', $result->costUsd);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.openai.com/v1/images/edits'
                && $request->hasHeader('Authorization', 'Bearer '.self::KEY)
                && $request['model'] === config('tryon.openai.model')
                && $request['quality'] === 'high'
                && count($request['images']) === 2
                && str_starts_with($request['images'][0]['image_url'], 'data:image/jpeg;base64,');
        });
    }

    public function test_requires_api_key_without_calling_openai(): void
    {
        Http::fake();

        try {
            (new OpenAiProvider)->generate(new TryOnInput($this->jpeg(), [], 'prompt', null));
            $this->fail('Exception kutilgan edi');
        } catch (TryOnProviderException $e) {
            $this->assertStringContainsString('kaliti', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    #[DataProvider('errorCases')]
    public function test_maps_openai_errors_to_user_messages(int $status, string $code, string $expected): void
    {
        Log::spy();
        Http::fake(['*' => Http::response(['error' => [
            'message' => 'Incorrect API key provided: '.self::KEY,
            'type' => 'invalid_request_error',
            'code' => $code,
        ]], $status)]);

        try {
            (new OpenAiProvider)->generate($this->input());
            $this->fail('Exception kutilgan edi');
        } catch (TryOnProviderException $e) {
            $this->assertStringContainsString($expected, $e->getMessage());
            $this->assertStringNotContainsString('SECRET', $e->getMessage());
        }

        // Log'ga faqat status/kod/tur — OpenAI xabar matni (unda kalit bo'lishi mumkin) emas.
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context) => ! str_contains(json_encode($context), 'SECRET')
        );
    }

    /** @return array<string, array{int, string, string}> */
    public static function errorCases(): array
    {
        return [
            'invalid key' => [401, 'invalid_api_key', 'kaliti noto\'g\'ri'],
            'no money' => [429, 'insufficient_quota', 'limit tugagan'],
            'rate limit' => [429, 'rate_limit_exceeded', 'chegarasiga'],
            'not verified' => [403, '', 'ruxsat yo\'q'],
            'moderation' => [400, 'moderation_blocked', 'xavfsizlik'],
            'server' => [500, '', 'vaqtinchalik'],
        ];
    }

    public function test_timeout_becomes_user_message(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->expectException(TryOnProviderException::class);
        $this->expectExceptionMessage('aloqa uzildi');

        (new OpenAiProvider)->generate($this->input());
    }

    public function test_empty_image_in_response_fails(): void
    {
        Http::fake(['*' => Http::response(['data' => [['b64_json' => '']]])]);

        $this->expectException(TryOnProviderException::class);
        (new OpenAiProvider)->generate($this->input());
    }

    public function test_container_binds_openai_provider_by_config(): void
    {
        config(['tryon.provider' => 'openai']);

        $this->assertInstanceOf(OpenAiProvider::class, app(TryOnProvider::class));
    }

    public function test_cost_without_details_counts_input_as_image_tokens(): void
    {
        $pricing = ['text_input' => 5, 'image_input' => 8, 'image_output' => 30];

        $this->assertSame('0.0380', ImageCost::fromUsage(['input_tokens' => 1000, 'output_tokens' => 1000], $pricing));
        $this->assertNull(ImageCost::fromUsage([], $pricing));
    }

    /** @param list<ReferenceImage> $references */
    private function input(array $references = []): TryOnInput
    {
        return new TryOnInput($this->jpeg(), $references, 'Virtual try-on test prompt', self::KEY, 'high');
    }

    private function jpeg(): string
    {
        static $bytes = null;
        if ($bytes === null) {
            $image = imagecreatetruecolor(8, 8);
            ob_start();
            imagejpeg($image);
            $bytes = (string) ob_get_clean();
        }

        return $bytes;
    }
}

<?php

namespace Tests\Feature\TryOn;

use App\Enums\TryOnStatus;
use App\Jobs\TryOnJob;
use App\Models\Fabric;
use App\Models\Look;
use App\Models\Product;
use App\Models\TryOnRequest;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TryOnHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();   // tests must not depend on `npm run dev` or a build
        Storage::fake('local', ['serve' => true]);
        config(['tryon.provider' => 'mock', 'tryon.mock.delay_seconds' => 0]);
        $this->seed(CatalogSeeder::class);
    }

    public function test_create_page_renders_catalog(): void
    {
        $this->get('/try-on')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('try-on/TryOnPage')
                ->has('styles', 4)
                ->where('keyMode', 'none')
                ->has('styles.0.looks.0.products')
                ->where('upload.max_kb', 10240)
                ->has('upload.allowed_mimes'));
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/try-on');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[^']+'/", $csp);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_store_queues_job_and_returns_202(): void
    {
        Queue::fake();

        $response = $this->postJson('/try-on/requests', $this->payload());

        $response->assertStatus(202)
            ->assertJsonPath('status', 'queued')
            ->assertJsonPath('suggested_size', '48');

        $id = $response->json('id');
        Queue::assertPushed(TryOnJob::class, fn (TryOnJob $job) => $job->requestId === $id);

        $tryOn = TryOnRequest::findOrFail($id);
        $this->assertNotNull($tryOn->upload_path);
        Storage::disk('local')->assertExists($tryOn->upload_path);
    }

    public function test_api_key_header_is_passed_to_job_but_never_stored(): void
    {
        Queue::fake();

        $response = $this->withHeader('X-OpenAI-Key', 'sk-test-NEVER-STORE-THIS-KEY-123456')
            ->postJson('/try-on/requests', $this->payload())
            ->assertStatus(202);

        Queue::assertPushed(TryOnJob::class, fn (TryOnJob $job) => $job->apiKey === 'sk-test-NEVER-STORE-THIS-KEY-123456');

        $row = json_encode(DB::table('try_on_requests')->where('id', $response->json('id'))->first());
        $this->assertStringNotContainsString('NEVER-STORE', (string) $row);
    }

    public function test_openai_mode_requires_valid_key(): void
    {
        config(['tryon.provider' => 'openai']);

        $this->postJson('/try-on/requests', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('api_key');
    }

    public function test_demo_mode_uses_mock_without_key_and_openai_with_key(): void
    {
        config(['tryon.provider' => 'auto']);
        Queue::fake();

        $this->get('/try-on')->assertInertia(fn (AssertableInertia $page) => $page->where('keyMode', 'optional'));

        $demo = $this->postJson('/try-on/requests', $this->payload())->assertStatus(202)->json('id');
        $this->assertSame('mock', TryOnRequest::findOrFail($demo)->provider);

        $real = $this->withHeader('X-OpenAI-Key', 'sk-test-DEMO-VISITOR-KEY-1234567890')
            ->postJson('/try-on/requests', $this->payload())->assertStatus(202)->json('id');
        $this->assertSame('openai', TryOnRequest::findOrFail($real)->provider);
    }

    public function test_demo_mode_rejects_a_malformed_key(): void
    {
        config(['tryon.provider' => 'auto']);

        $this->withHeader('X-OpenAI-Key', 'not-a-key')
            ->postJson('/try-on/requests', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('api_key');
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->postJson('/try-on/requests', [
            'height_cm' => 300,
            'look_id' => 999999,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['photo', 'height_cm', 'look_id', 'consent']);
    }

    public function test_suit_fabric_must_be_available_for_the_jacket_too(): void
    {
        // Suit (smart-navy-knit): detach the check fabric from the blazer — the trousers have it, the jacket does not.
        $greyCheck = Fabric::where('slug', 'grey-check')->value('id');
        Product::where('slug', 'blazer-navy')->firstOrFail()->fabrics()->detach($greyCheck);

        $this->postJson('/try-on/requests', $this->payload(['fabric_id' => $greyCheck]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('fabric_id');
    }

    public function test_create_page_sends_suit_flag_and_jacket_fabrics(): void
    {
        $this->get('/try-on')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('styles.0.looks.0.is_suit', true)                      // classic-navy-suit
            ->has('styles.0.looks.0.products.1.fabrics', 7));               // blazer-navy: 7 wool fabrics
    }

    public function test_fabric_must_belong_to_look_trousers(): void
    {
        // The linen look's trousers only come in beige linen — the check fabric is not allowed.
        $payload = $this->payload([
            'look_id' => Look::where('slug', 'summer-linen')->value('id'),
            'fabric_id' => Fabric::where('slug', 'grey-check')->value('id'),
        ]);

        $this->postJson('/try-on/requests', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('fabric_id');
    }

    public function test_fake_image_with_jpg_extension_is_rejected(): void
    {
        $payload = $this->payload(['photo' => UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo 1;')]);

        $this->postJson('/try-on/requests', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    public function test_full_flow_with_mock_provider(): void
    {
        // phpunit.xml: QUEUE_CONNECTION=sync — the job runs immediately.
        $id = $this->postJson('/try-on/requests', $this->payload())->assertStatus(202)->json('id');

        $this->getJson("/try-on/requests/{$id}")
            ->assertOk()
            ->assertJsonPath('status', 'done')
            ->assertJsonPath('cost_usd', '0.0000')
            ->assertJson(fn ($json) => $json->whereType('result_url', 'string')->etc());
    }

    public function test_fabric_swap_reuses_previous_photo(): void
    {
        Queue::fake();
        $first = $this->postJson('/try-on/requests', $this->payload())->json('id');

        $second = $this->postJson('/try-on/requests', [
            ...collect($this->payload())->except('photo')->all(),
            'source_request_id' => $first,
            'fabric_id' => Fabric::where('slug', 'charcoal-pinstripe')->value('id'),
        ])->assertStatus(202)->json('id');

        $this->assertSame(
            TryOnRequest::findOrFail($first)->upload_path,
            TryOnRequest::findOrFail($second)->upload_path,
        );
    }

    public function test_cannot_see_other_sessions_requests(): void
    {
        Queue::fake();
        $id = $this->postJson('/try-on/requests', $this->payload())->json('id');

        $this->flushSession();

        $this->getJson("/try-on/requests/{$id}")->assertNotFound();
        $this->deleteJson("/try-on/requests/{$id}")->assertNotFound();
    }

    public function test_destroy_deletes_photo_and_cancels(): void
    {
        Queue::fake();
        $id = $this->postJson('/try-on/requests', $this->payload())->json('id');
        $path = (string) TryOnRequest::findOrFail($id)->upload_path;

        $this->deleteJson("/try-on/requests/{$id}")->assertNoContent();

        Storage::disk('local')->assertMissing($path);
        $tryOn = TryOnRequest::findOrFail($id);
        $this->assertNull($tryOn->upload_path);
        $this->assertSame(TryOnStatus::Cancelled, $tryOn->status);
    }

    public function test_store_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/try-on/requests', [])->assertStatus(422);
        }

        $this->postJson('/try-on/requests', [])->assertStatus(429);
    }

    /** @param  array<string, mixed>  $overrides
     *  @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'photo' => UploadedFile::fake()->image('me.jpg', 600, 900),
            'height_cm' => 178,
            'waist_cm' => 84,
            'size' => '48',
            'look_id' => Look::where('slug', 'smart-navy-knit')->value('id'),
            'fabric_id' => Fabric::where('slug', 'grey-check')->value('id'),
            'quality' => 'medium',
            'consent' => true,
            ...$overrides,
        ];
    }
}

<?php

namespace Tests\Feature\TryOn;

use App\Enums\TryOnStatus;
use App\Jobs\TryOnJob;
use App\Models\Fabric;
use App\Models\Look;
use App\Models\TryOnRequest;
use App\TryOn\ImageService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TryOnJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['tryon.provider' => 'mock', 'tryon.mock.delay_seconds' => 0]);
        $this->seed(CatalogSeeder::class);
    }

    public function test_mock_job_completes_and_stores_result(): void
    {
        $request = $this->makeRequest();

        TryOnJob::dispatchSync($request->id);

        $request->refresh();
        $this->assertSame(TryOnStatus::Done, $request->status);
        $this->assertSame('mock-v1', $request->model);
        $this->assertSame('0.0000', $request->cost_usd);
        $this->assertNotNull($request->started_at);
        $this->assertNotNull($request->finished_at);
        $this->assertNotNull($request->result_path);
        Storage::disk('local')->assertExists($request->result_path);
    }

    public function test_job_does_nothing_if_request_is_not_queued(): void
    {
        $request = $this->makeRequest();
        $request->update(['status' => TryOnStatus::Done]);

        TryOnJob::dispatchSync($request->id);

        $this->assertNull($request->fresh()?->result_path);   // the provider was never called
    }

    public function test_missing_upload_marks_request_failed(): void
    {
        $request = $this->makeRequest();
        Storage::disk('local')->delete((string) $request->upload_path);

        TryOnJob::dispatchSync($request->id);

        $request->refresh();
        $this->assertSame(TryOnStatus::Failed, $request->status);
        $this->assertNotEmpty($request->error);
    }

    public function test_api_key_is_encrypted_in_queue_payload(): void
    {
        config(['queue.default' => 'database']);
        $request = $this->makeRequest();

        TryOnJob::dispatch($request->id, 'sk-test-SUPERSECRET-123');

        $payload = (string) DB::table('jobs')->value('payload');
        $this->assertNotSame('', $payload);
        $this->assertStringNotContainsString('sk-test-SUPERSECRET-123', $payload);
        $this->assertStringNotContainsString('SUPERSECRET', $payload);
    }

    public function test_job_never_retries(): void
    {
        $job = new TryOnJob('x');

        $this->assertSame(1, $job->tries);
        $this->assertTrue($job->failOnTimeout);
        $this->assertGreaterThan((int) config('tryon.openai.timeout'), $job->timeout);
    }

    private function makeRequest(): TryOnRequest
    {
        $images = app(ImageService::class);
        $gd = imagecreatetruecolor(300, 500);
        ob_start();
        imagejpeg($gd);
        $path = $images->storeUpload($images->sanitize((string) ob_get_clean()));

        return TryOnRequest::create([
            'look_id' => Look::where('slug', 'smart-navy-knit')->value('id'),
            'fabric_id' => Fabric::where('slug', 'grey-check')->value('id'),
            'height_cm' => 178,
            'size' => '48',
            'params' => ['quality' => 'medium'],
            'prompt_version' => (string) config('tryon.prompt_version'),
            'provider' => 'mock',
            'upload_path' => $path,
        ]);
    }
}

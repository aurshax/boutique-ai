<?php

namespace Tests\Feature\TryOn;

use App\Enums\TryOnStatus;
use App\Models\TryOnRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneTryOnRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_old_upload_is_deleted_but_shared_recent_one_is_kept(): void
    {
        Storage::disk('local')->put('uploads/old.jpg', 'x');
        Storage::disk('local')->put('uploads/shared.jpg', 'x');

        $old = $this->make(['upload_path' => 'uploads/old.jpg'], now()->subHours(30));
        // shared.jpg: an old request + a recent fabric-swap request → must not be deleted yet
        $this->make(['upload_path' => 'uploads/shared.jpg'], now()->subHours(30));
        $this->make(['upload_path' => 'uploads/shared.jpg'], now()->subHour());

        $this->artisan('try-on:prune')->assertSuccessful();

        Storage::disk('local')->assertMissing('uploads/old.jpg');
        Storage::disk('local')->assertExists('uploads/shared.jpg');
        $this->assertNull($old->fresh()?->upload_path);
    }

    public function test_old_results_are_deleted(): void
    {
        Storage::disk('local')->put('results/r.png', 'x');
        $tryOn = $this->make(['result_path' => 'results/r.png', 'status' => TryOnStatus::Done], now()->subDays(8));

        $this->artisan('try-on:prune')->assertSuccessful();

        Storage::disk('local')->assertMissing('results/r.png');
        $this->assertNull($tryOn->fresh()?->result_path);
    }

    public function test_stuck_requests_are_failed(): void
    {
        $running = $this->make(['status' => TryOnStatus::Running], now()->subMinutes(20));
        $queued = $this->make(['status' => TryOnStatus::Queued], now()->subMinutes(40));
        $fresh = $this->make(['status' => TryOnStatus::Queued], now());

        $this->artisan('try-on:prune')->assertSuccessful();

        $this->assertSame(TryOnStatus::Failed, $running->fresh()?->status);
        $this->assertSame(TryOnStatus::Failed, $queued->fresh()?->status);
        $this->assertSame(TryOnStatus::Queued, $fresh->fresh()?->status);
    }

    /** @param  array<string, mixed>  $attributes */
    private function make(array $attributes, \DateTimeInterface $at): TryOnRequest
    {
        $tryOn = TryOnRequest::create([
            'height_cm' => 178,
            'prompt_version' => 'v1',
            'provider' => 'mock',
            ...$attributes,
        ]);

        // Push created_at/updated_at into the past by hand.
        TryOnRequest::whereKey($tryOn->id)->update(['created_at' => $at, 'updated_at' => $at]);

        return $tryOn->fresh() ?? $tryOn;
    }
}

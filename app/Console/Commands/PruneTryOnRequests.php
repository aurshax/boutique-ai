<?php

namespace App\Console\Commands;

use App\Enums\TryOnStatus;
use App\Models\TryOnRequest;
use App\TryOn\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes customer photos after their TTL and closes requests that got stuck.
 * The scheduler runs it hourly (routes/console.php). Manually: sail artisan try-on:prune
 */
class PruneTryOnRequests extends Command
{
    protected $signature = 'try-on:prune';

    protected $description = 'Delete expired try-on images and close stuck requests';

    public function handle(ImageService $images): int
    {
        $uploadCutoff = now()->subHours((int) config('tryon.ttl.uploads_hours'));
        $resultCutoff = now()->subDays((int) config('tryon.ttl.results_days'));

        // 1) Uploaded photos. One photo can be shared by several requests (fabric swaps),
        //    so it expires counting from the LATEST request: GROUP BY ... HAVING max(created_at) < cutoff
        $oldUploads = DB::table('try_on_requests')
            ->whereNotNull('upload_path')
            ->groupBy('upload_path')
            ->havingRaw('max(created_at) < ?', [$uploadCutoff])
            ->pluck('upload_path');

        foreach ($oldUploads as $path) {
            $images->delete($path);
            TryOnRequest::where('upload_path', $path)->update(['upload_path' => null]);
        }

        // 2) Result images
        $oldResults = TryOnRequest::whereNotNull('result_path')->where('created_at', '<', $resultCutoff)->get();
        foreach ($oldResults as $tryOn) {
            $images->delete($tryOn->result_path);
            $tryOn->update(['result_path' => null]);
        }

        // 3) Stuck requests: running > 10 min (job timeout is 3 min) or queued > 30 min (no worker running).
        $stuck = TryOnRequest::query()
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('status', TryOnStatus::Running->value)->where('updated_at', '<', now()->subMinutes(10)))
                ->orWhere(fn ($q) => $q->where('status', TryOnStatus::Queued->value)->where('created_at', '<', now()->subMinutes(30))))
            ->update([
                'status' => TryOnStatus::Failed->value,
                'error' => __('tryon.errors.stuck'),
                'finished_at' => now(),
            ]);

        $this->info(sprintf('Deleted: %d uploaded photo(s), %d result(s). Closed: %d stuck request(s).',
            $oldUploads->count(), $oldResults->count(), $stuck));

        return self::SUCCESS;
    }
}

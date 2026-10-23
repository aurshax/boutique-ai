<?php

namespace App\Console\Commands;

use App\Enums\TryOnStatus;
use App\Models\TryOnRequest;
use App\TryOn\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mijoz rasmlarini muddati o'tgach o'chiradi va "osilib qolgan" so'rovlarni yopadi.
 * Scheduler har soatda ishga tushiradi (routes/console.php). Qo'lda: sail artisan try-on:prune
 */
class PruneTryOnRequests extends Command
{
    protected $signature = 'try-on:prune';

    protected $description = 'Eski try-on rasmlarini o\'chirish va osilib qolgan so\'rovlarni yopish';

    public function handle(ImageService $images): int
    {
        $uploadCutoff = now()->subHours((int) config('tryon.ttl.uploads_hours'));
        $resultCutoff = now()->subDays((int) config('tryon.ttl.results_days'));

        // 1) Yuklangan rasmlar. Bitta rasmni bir nechta so'rov ishlatishi mumkin (tkan almashtirish),
        //    shuning uchun rasm ENG OXIRGI so'rovdan hisoblab o'chiriladi: GROUP BY ... HAVING max(created_at) < cutoff
        $oldUploads = DB::table('try_on_requests')
            ->whereNotNull('upload_path')
            ->groupBy('upload_path')
            ->havingRaw('max(created_at) < ?', [$uploadCutoff])
            ->pluck('upload_path');

        foreach ($oldUploads as $path) {
            $images->delete($path);
            TryOnRequest::where('upload_path', $path)->update(['upload_path' => null]);
        }

        // 2) Natija rasmlari
        $oldResults = TryOnRequest::whereNotNull('result_path')->where('created_at', '<', $resultCutoff)->get();
        foreach ($oldResults as $tryOn) {
            $images->delete($tryOn->result_path);
            $tryOn->update(['result_path' => null]);
        }

        // 3) Osilib qolganlar: running > 10 daqiqa (job timeout 3 daqiqa) yoki queued > 30 daqiqa (worker ishlamayapti).
        $stuck = TryOnRequest::query()
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('status', TryOnStatus::Running->value)->where('updated_at', '<', now()->subMinutes(10)))
                ->orWhere(fn ($q) => $q->where('status', TryOnStatus::Queued->value)->where('created_at', '<', now()->subMinutes(30))))
            ->update([
                'status' => TryOnStatus::Failed->value,
                'error' => 'Vaqt tugadi. Qaytadan urinib ko\'ring.',
                'finished_at' => now(),
            ]);

        $this->info(sprintf('O\'chirildi: %d yuklangan rasm, %d natija. Yopildi: %d osilib qolgan so\'rov.',
            $oldUploads->count(), $oldResults->count(), $stuck));

        return self::SUCCESS;
    }
}

<?php

namespace App\Models;

use App\Enums\TryOnStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bitta "kiyib ko'rish" so'rovi. OpenAI kaliti va mijozning shaxsiy ma'lumotlari bu yerda saqlanmaydi.
 *
 * @property string $id
 * @property TryOnStatus $status
 * @property int|null $look_id
 * @property int|null $fabric_id
 * @property int $height_cm
 * @property int|null $waist_cm
 * @property string|null $size
 * @property array<string, mixed> $params
 * @property string $prompt_version
 * @property string $provider
 * @property string|null $model
 * @property string|null $upload_path
 * @property string|null $result_path
 * @property string|null $cost_usd
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'status', 'look_id', 'fabric_id', 'height_cm', 'waist_cm', 'size',
    'params', 'prompt_version', 'provider', 'model',
    'upload_path', 'result_path', 'cost_usd', 'error', 'started_at', 'finished_at',
])]
class TryOnRequest extends Model
{
    use HasUuids;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'queued',
        'params' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'status' => TryOnStatus::class,
            'params' => 'array',
            'cost_usd' => 'decimal:4',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Look, $this> */
    public function look(): BelongsTo
    {
        return $this->belongsTo(Look::class);
    }

    /** @return BelongsTo<Fabric, $this> */
    public function fabric(): BelongsTo
    {
        return $this->belongsTo(Fabric::class);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Enums\TryOnStatus;
use App\Http\Middleware\ExtractOpenAiKey;
use App\Http\Requests\CreateTryOnRequest;
use App\Jobs\TryOnJob;
use App\Models\Fabric;
use App\Models\Look;
use App\Models\Product;
use App\Models\Style;
use App\Models\TryOnRequest;
use App\TryOn\Exceptions\InvalidImageException;
use App\TryOn\ImageService;
use App\TryOn\ProviderFactory;
use App\TryOn\SizeSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TryOnController extends Controller
{
    private const SESSION_KEY = 'try_on.ids';

    private const SESSION_LIMIT = 50;

    /** Try-on page: the catalogue as Inertia props. */
    public function create(ProviderFactory $providers): InertiaResponse
    {
        $styles = Style::with(['looks.products' => fn ($q) => $q->with('fabrics')])
            ->orderBy('sort')
            ->get()
            ->map(fn (Style $style) => [
                'id' => $style->id,
                'slug' => $style->slug,
                'name' => $style->name,
                'description' => $style->description,
                'looks' => $style->looks->map(fn (Look $look) => [
                    'id' => $look->id,
                    'name' => $look->name,
                    'is_suit' => $look->is_suit,
                    'total_price_uzs' => $look->totalPriceUzs(),
                    'products' => $look->products->map(fn (Product $p) => [
                        'id' => $p->id,
                        'type' => $p->type->value,
                        'type_label' => $p->type->label(),
                        'name' => $p->name,
                        'price_uzs' => $p->price_uzs,
                        'image' => $p->image ? '/'.$p->image : null,
                        // Trousers and blazer fabrics (each with its own surcharge) — a suit needs both.
                        'fabrics' => $p->type->supportsFabricSwap()
                            ? $p->fabrics->map(fn (Fabric $f) => [
                                'id' => $f->id,
                                'name' => $f->name,
                                'composition' => $f->composition,
                                'pattern' => $f->pattern->value,
                                'color_hex' => $f->color_hex,
                                'swatch_image' => $f->swatch_image,
                                'extra_price_uzs' => (int) data_get($f, 'pivot.extra_price_uzs'),   // pivot column (withPivot)
                            ])->values()
                            : [],
                    ])->values(),
                ])->values(),
            ]);

        return Inertia::render('try-on/TryOnPage', [
            'styles' => $styles,
            'sizes' => CreateTryOnRequest::SIZES,
            // 'required' | 'optional' (demo: mock unless the visitor brings a key) | 'none'
            'keyMode' => $providers->keyMode(),
            'estimateUsd' => config('tryon.estimate_usd'),
            // For the browser pre-check (features/upload-photo). The real check is CreateTryOnRequest.
            'upload' => [
                'allowed_mimes' => config('tryon.upload.allowed_mimes'),
                'max_kb' => (int) config('tryon.upload.max_kb'),
            ],
        ]);
    }

    /** New request: sanitises and stores the photo, queues the job. 202 Accepted. */
    public function store(CreateTryOnRequest $request, ImageService $images, SizeSuggester $sizes, ProviderFactory $providers): JsonResponse
    {
        $data = $request->validated();
        $apiKey = $request->attributes->get(ExtractOpenAiKey::ATTRIBUTE);
        $look = Look::with('products')->findOrFail((int) $data['look_id']);

        $sourceId = $data['source_request_id'] ?? null;
        if ($sourceId !== null) {
            $source = $this->owned($request, $sourceId);
            if (! $images->exists($source->upload_path)) {
                throw ValidationException::withMessages(['photo' => __('tryon.validation.source_photo_deleted')]);
            }
            $uploadPath = $source->upload_path;
        } else {
            try {
                $uploadPath = $images->storeUpload($images->sanitize((string) $request->file('photo')?->get()));
            } catch (InvalidImageException $e) {
                throw ValidationException::withMessages(['photo' => $e->getMessage()]);
            }
        }

        $trousers = $look->products->first(fn (Product $p) => $p->type === ProductType::Trousers);
        $waist = isset($data['waist_cm']) ? (int) $data['waist_cm'] : null;
        $suggested = $trousers !== null
            ? $sizes->suggest($trousers, (int) $data['height_cm'], $waist, $data['size'] ?? null)
            : null;

        $tryOn = TryOnRequest::create([
            'look_id' => $look->id,
            'fabric_id' => $data['fabric_id'] ?? null,
            'height_cm' => (int) $data['height_cm'],
            'waist_cm' => $waist,
            'size' => $data['size'] ?? null,
            'params' => [
                'quality' => $data['quality'] ?? config('tryon.openai.quality'),
                'product_ids' => $look->products->pluck('id')->all(),
                'suggested_size' => $suggested,
                'source_request_id' => $sourceId,
            ],
            'prompt_version' => (string) config('tryon.prompt_version'),
            'provider' => $providers->nameFor($apiKey),
            'upload_path' => $uploadPath,
        ]);

        $this->remember($request, $tryOn->id);

        TryOnJob::dispatch($tryOn->id, $apiKey);

        return response()->json($this->present($tryOn), 202);
    }

    /** Polling: status, plus a 10-minute signed link once the result is ready. */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json($this->present($this->owned($request, $id)));
    }

    /**
     * "Delete my photo" / "Stop waiting".
     * Images are deleted at once. A running request becomes cancelled (the OpenAI call may still be billed).
     */
    public function destroy(Request $request, string $id, ImageService $images): Response
    {
        $tryOn = $this->owned($request, $id);

        // Other requests (fabric swaps) may reuse this photo — detach it from all of them.
        if ($tryOn->upload_path !== null) {
            $images->delete($tryOn->upload_path);
            TryOnRequest::where('upload_path', $tryOn->upload_path)->update(['upload_path' => null]);
        }
        $images->delete($tryOn->result_path);

        $tryOn->refresh();
        $tryOn->update([
            'result_path' => null,
            'status' => $tryOn->status->isFinished() ? $tryOn->status : TryOnStatus::Cancelled,
            'finished_at' => $tryOn->finished_at ?? now(),
        ]);

        return response()->noContent();
    }

    /** Only requests created in this browser session. Anyone else's is a 404 (we do not even reveal it exists). */
    private function owned(Request $request, string $id): TryOnRequest
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        abort_unless(in_array($id, $ids, true), 404);

        return TryOnRequest::findOrFail($id);
    }

    private function remember(Request $request, string $id): void
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $id;
        $request->session()->put(self::SESSION_KEY, array_slice($ids, -self::SESSION_LIMIT));
    }

    /** @return array<string, mixed> */
    private function present(TryOnRequest $tryOn): array
    {
        $resultUrl = null;
        if ($tryOn->status === TryOnStatus::Done && $tryOn->result_path !== null) {
            $resultUrl = app(ImageService::class)->temporaryUrl($tryOn->result_path);
        }

        return [
            'id' => $tryOn->id,
            'status' => $tryOn->status->value,
            'provider' => $tryOn->provider,   // 'mock' results are labelled as a demo in the UI
            'error' => $tryOn->error,
            'result_url' => $resultUrl,
            'has_photo' => $tryOn->upload_path !== null,
            'suggested_size' => $tryOn->params['suggested_size'] ?? null,
            'cost_usd' => $tryOn->cost_usd,
            'look_id' => $tryOn->look_id,
            'fabric_id' => $tryOn->fabric_id,
            'created_at' => $tryOn->created_at?->toIso8601String(),
        ];
    }
}

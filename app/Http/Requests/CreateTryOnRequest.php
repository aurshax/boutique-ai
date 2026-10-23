<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Http\Middleware\ExtractOpenAiKey;
use App\Models\Look;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a new try-on request.
 * Two modes: a new photo (`photo`) or reuse of a previous request's photo
 * (`source_request_id`) — so a fabric swap does not make the customer upload again.
 */
class CreateTryOnRequest extends FormRequest
{
    /** @var list<string> */
    public const SIZES = ['44', '46', '48', '50', '52', '54', '56'];

    public function authorize(): bool
    {
        return true;   // no login; ownership is checked through the session
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'photo' => ['required_without:source_request_id', 'prohibits:source_request_id', 'file',
                'mimes:jpg,jpeg,png,webp', 'max:'.config('tryon.upload.max_kb')],
            'source_request_id' => ['nullable', 'uuid'],
            'height_cm' => ['required', 'integer', 'between:120,230'],
            'waist_cm' => ['nullable', 'integer', 'between:50,160'],
            'size' => ['nullable', Rule::in(self::SIZES)],
            'look_id' => ['required', 'integer', 'exists:looks,id'],
            'fabric_id' => ['nullable', 'integer', 'exists:fabrics,id'],
            'quality' => ['nullable', Rule::in(['medium', 'high'])],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // 'openai' mode: the key is required. 'auto' (demo): optional, but must be valid when given.
                $mode = config('tryon.provider');
                $key = $this->attributes->get(ExtractOpenAiKey::ATTRIBUTE);
                $required = $mode === 'openai';
                $given = is_string($key) && $key !== '';
                if (($required || ($mode === 'auto' && $given)) && ! ($given && preg_match('/^sk-[A-Za-z0-9_\-]{20,200}$/', $key))) {
                    $validator->errors()->add('api_key', __('tryon.validation.api_key'));
                }

                // Is the chosen fabric available for EVERY product it applies to in this look?
                // (separates: the trousers; suit: trousers and blazer)
                $fabricId = $this->integer('fabric_id') ?: null;
                if ($fabricId !== null) {
                    $look = Look::with('products.fabrics')->find($this->integer('look_id'));
                    $targets = $look?->products->filter(fn ($p) => $look->followsFabric($p)) ?? collect();
                    $hasTrousers = $targets->contains(fn ($p) => $p->type === ProductType::Trousers);

                    if (! $hasTrousers || $targets->contains(fn ($p) => ! $p->fabrics->contains('id', $fabricId))) {
                        $validator->errors()->add('fabric_id', __('tryon.validation.fabric_unavailable'));
                    }
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'photo.required_without' => __('tryon.validation.photo_required'),
            'photo.mimes' => __('tryon.image.wrong_type'),
            'photo.max' => __('tryon.image.too_large', ['mb' => (int) round(config('tryon.upload.max_kb') / 1024)]),
            'height_cm.between' => __('tryon.validation.height_between'),
            'waist_cm.between' => __('tryon.validation.waist_between'),
            'consent.accepted' => __('tryon.validation.consent'),
        ];
    }
}

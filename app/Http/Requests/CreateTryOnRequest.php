<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Http\Middleware\ExtractOpenAiKey;
use App\Models\Look;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Yangi try-on so'rovi validatsiyasi.
 * Ikki rejim: yangi rasm (`photo`) yoki oldingi so'rovning rasmini qayta ishlatish
 * (`source_request_id`) — tkan almashtirishda mijoz rasmni qayta yuklamaydi.
 */
class CreateTryOnRequest extends FormRequest
{
    /** @var list<string> */
    public const SIZES = ['44', '46', '48', '50', '52', '54', '56'];

    public function authorize(): bool
    {
        return true;   // login yo'q; egalik session orqali tekshiriladi
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

                // OpenAI rejimida kalit majburiy va formati tekshiriladi (mock'da kerak emas).
                if (config('tryon.provider') === 'openai') {
                    $key = $this->attributes->get(ExtractOpenAiKey::ATTRIBUTE);
                    if (! is_string($key) || ! preg_match('/^sk-[A-Za-z0-9_\-]{20,200}$/', $key)) {
                        $validator->errors()->add('api_key', 'OpenAI API kaliti kiritilmagan yoki formati noto\'g\'ri.');
                    }
                }

                // Tanlangan tkan shu look'dagi shim uchun ruxsat etilganmi?
                $fabricId = $this->integer('fabric_id') ?: null;
                if ($fabricId !== null) {
                    $trousers = Look::with('products.fabrics')->find($this->integer('look_id'))
                        ?->products->first(fn ($p) => $p->type === ProductType::Trousers);

                    if ($trousers === null || ! $trousers->fabrics->contains('id', $fabricId)) {
                        $validator->errors()->add('fabric_id', 'Bu tkan tanlangan look\'dagi shim uchun mavjud emas.');
                    }
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'photo.required_without' => 'Rasm yuklang.',
            'photo.mimes' => 'Faqat JPEG, PNG yoki WebP rasm.',
            'photo.max' => 'Rasm 10 MB dan oshmasligi kerak.',
            'height_cm.between' => 'Bo\'y 120–230 sm oralig\'ida bo\'lishi kerak.',
            'waist_cm.between' => 'Bel o\'lchami 50–160 sm oralig\'ida bo\'lishi kerak.',
            'consent.accepted' => 'Rasm OpenAI\'ga yuborilishiga rozilik bering.',
        ];
    }
}

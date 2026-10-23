<?php

return [

    /*
    | Qaysi AI provider ishlaydi: 'mock' (bepul, development) yoki 'openai' (mijoz kaliti bilan, BYOK).
    */
    'provider' => env('TRYON_PROVIDER', 'mock'),

    // Prompt shabloni versiyasi — har bir so'rovda saqlanadi (natijalarni solishtirish uchun).
    'prompt_version' => 'v3',

    'upload' => [
        'max_kb' => 10240,                                   // 10 MB
        'max_side' => 1536,                                  // eng uzun tomon, px: yuz detallari saqlansin (1024 da yuz "qayta chizilardi")
        'jpeg_quality' => 90,
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    // Rasmlar qancha saqlanadi (Scheduler tozalaydi — 7-task).
    'ttl' => [
        'uploads_hours' => 24,
        'results_days' => 7,
    ],

    'mock' => [
        // Kutish animatsiyasini sinash uchun sun'iy kechikish (soniya).
        'delay_seconds' => (int) env('TRYON_MOCK_DELAY', 3),
    ],

    'openai' => [
        'base_url' => env('TRYON_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('TRYON_OPENAI_MODEL', 'gpt-image-2.5-sunburst'),
        'quality' => env('TRYON_OPENAI_QUALITY', 'medium'),
        'output_longest_side' => 1536,                       // natija o'lchami kirish nisbatida (Support\\OutputSize)
        'input_fidelity' => env('TRYON_OPENAI_INPUT_FIDELITY'),  // 'high' — faqat gpt-image-1/1.5; 2.x uchun bo'sh qoldiring
        'output_compression' => 90,                          // natija JPEG (PNG'dan ~5x kichik)
        'timeout' => 170,                                    // job timeout'idan (180) kichik
        'connect_timeout' => 10,

        // USD, 1M token uchun (developers.openai.com/api/docs/pricing, 2026-10-22). Narx o'zgarsa — shu yerda.
        // Ro'yxatda yo'q model uchun cost_usd = null (usage baribir params.usage da saqlanadi).
        'pricing' => [
            'gpt-image-2.5-sunburst' => ['text_input' => 5.00, 'image_input' => 8.00, 'image_output' => 30.00],
            'gpt-image-2.5-flare' => ['text_input' => 5.00, 'image_input' => 8.00, 'image_output' => 30.00],
            'gpt-image-2' => ['text_input' => 2.50, 'image_input' => 4.00, 'image_output' => 15.00],
        ],
    ],

    // catalog:generate-images natijalari shu papka ichiga (media/catalog, media/fabrics). Testda vaqtinchalik papka.
    'catalog_media_path' => public_path(),

    // Generatsiyadan oldin mijozga ko'rsatiladigan taxminiy narx (USD, bitta look, 4 ta kirish rasmi).
    'estimate_usd' => [
        'medium' => 0.05,
        'high' => 0.09,
    ],

    'job' => [
        'timeout' => 180,                                    // DB_QUEUE_RETRY_AFTER undan katta bo'lishi shart (200)
    ],

];

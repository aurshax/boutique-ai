<?php

return [

    /*
    | Qaysi AI provider ishlaydi: 'mock' (bepul, development) yoki 'openai'.
    | OpenAI provider 11-taskda qo'shiladi.
    */
    'provider' => env('TRYON_PROVIDER', 'mock'),

    // Prompt shabloni versiyasi — har bir so'rovda saqlanadi (natijalarni solishtirish uchun).
    'prompt_version' => 'v1',

    'upload' => [
        'max_kb' => 10240,                                   // 10 MB
        'max_side' => 1024,                                  // eng uzun tomon, px (narx va tezlik uchun)
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
        'model' => env('TRYON_OPENAI_MODEL', 'gpt-image-2.5-sunburst'),
        'quality' => env('TRYON_OPENAI_QUALITY', 'medium'),
        'size' => '1024x1536',
        'timeout' => 170,                                    // job timeout'idan (180) kichik
        'connect_timeout' => 10,
    ],

    // Generatsiyadan oldin mijozga ko'rsatiladigan taxminiy narx (USD, bitta look, 4 ta kirish rasmi).
    'estimate_usd' => [
        'medium' => 0.05,
        'high' => 0.09,
    ],

    'job' => [
        'timeout' => 180,                                    // DB_QUEUE_RETRY_AFTER undan katta bo'lishi shart (200)
    ],

];

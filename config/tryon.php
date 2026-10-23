<?php

return [

    /*
    | Which AI provider runs a try-on (App\TryOn\ProviderFactory):
    |  - mock   — free fake result, for development;
    |  - openai — real AI, every visitor must bring their own OpenAI key (BYOK);
    |  - auto   — public demo: real AI when the visitor enters a key, otherwise the free mock.
    */
    'provider' => env('TRYON_PROVIDER', 'mock'),

    // Prompt template version — stored with every request so results can be compared.
    'prompt_version' => 'v3',

    'upload' => [
        'max_kb' => 10240,                                   // 10 MB
        'max_side' => 1536,                                  // longest side in px: enough face detail (at 1024 faces got redrawn)
        'jpeg_quality' => 90,
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    // Private disk for customer photos and results: 'local' in development, a private S3/R2 bucket
    // in production (see config/filesystems.php 's3'). Never a public disk.
    'disk' => env('TRYON_DISK', 'local'),

    // How long images are kept (the scheduler runs try-on:prune hourly).
    'ttl' => [
        'uploads_hours' => 24,
        'results_days' => 7,
    ],

    'mock' => [
        // Artificial delay (seconds) to exercise the waiting screen.
        'delay_seconds' => (int) env('TRYON_MOCK_DELAY', 3),
    ],

    'openai' => [
        'base_url' => env('TRYON_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('TRYON_OPENAI_MODEL', 'gpt-image-2.5-sunburst'),
        'quality' => env('TRYON_OPENAI_QUALITY', 'medium'),
        'output_longest_side' => 1536,                       // output keeps the input aspect ratio (Support\OutputSize)
        'input_fidelity' => env('TRYON_OPENAI_INPUT_FIDELITY'),  // 'high' — gpt-image-1/1.5 only; leave empty for 2.x
        'output_compression' => 90,                          // JPEG output (~5x smaller than PNG)
        'timeout' => 170,                                    // must stay below the job timeout (180)
        'connect_timeout' => 10,

        // USD per 1M tokens (developers.openai.com/api/docs/pricing, 2026-10-22). Update here when prices change.
        // Models missing from this list get cost_usd = null (usage is still kept in params.usage).
        'pricing' => [
            'gpt-image-2.5-sunburst' => ['text_input' => 5.00, 'image_input' => 8.00, 'image_output' => 30.00],
            'gpt-image-2.5-flare' => ['text_input' => 5.00, 'image_input' => 8.00, 'image_output' => 30.00],
            'gpt-image-2' => ['text_input' => 2.50, 'image_input' => 4.00, 'image_output' => 15.00],
        ],
    ],

    // Where catalog:generate-images writes (media/catalog, media/fabrics). Tests point it at a temp dir.
    'catalog_media_path' => public_path(),

    // Rough per-look cost shown before generating (USD).
    'estimate_usd' => [
        'medium' => 0.05,
        'high' => 0.09,
    ],

    'job' => [
        'timeout' => 180,                                    // DB_QUEUE_RETRY_AFTER must be larger (200)
    ],

];

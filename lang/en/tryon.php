<?php

// User-facing try-on messages. Add lang/uz/tryon.php (same keys) for another language.
return [

    'image' => [
        'too_large' => 'The photo is empty or too large (up to :mb MB).',
        'wrong_type' => 'Only JPEG, PNG or WebP photos are supported.',
        'unreadable' => 'We could not read this photo. Please try another one.',
        'too_many_pixels' => 'The photo resolution is too high.',
        'provider_returned_no_image' => 'The AI did not return an image. Please try again.',
        'missing' => 'The photo is no longer available. Please upload it again.',
    ],

    'validation' => [
        'photo_required' => 'Please upload a photo.',
        'source_photo_deleted' => 'Your previous photo has been deleted. Please upload it again.',
        'height_between' => 'Height must be between 120 and 230 cm.',
        'waist_between' => 'Waist must be between 50 and 160 cm.',
        'consent' => 'Please agree to your photo being sent to OpenAI.',
        'api_key' => 'The OpenAI API key is missing or has an invalid format.',
        'fabric_unavailable' => 'This fabric is not available for the selected look.',
    ],

    'errors' => [
        'request_incomplete' => 'The request has no look or photo.',
        'internal' => 'Something went wrong on our side. Please try again.',
        'timeout' => 'Time is up — the AI did not respond.',
        'stuck' => 'The request timed out. Please try again.',
    ],

    'openai' => [
        'missing_key' => 'No OpenAI API key was provided.',
        'connection' => 'Could not reach OpenAI or it took too long to answer. Please try again shortly.',
        'no_image' => 'OpenAI did not return an image.',
        'invalid_key' => 'Your OpenAI key is invalid or revoked. Please check it and enter it again.',
        'no_quota' => 'Your OpenAI account is out of credit or has hit its limit. See platform.openai.com → Billing / Limits.',
        'rate_limited' => 'OpenAI rate limit reached. Please try again in a minute.',
        'forbidden' => 'This key is not allowed to use the model (OpenAI organization verification or project permissions may be required).',
        'moderation' => 'OpenAI declined to process this photo under its safety rules. Please try another photo.',
        'bad_request' => 'OpenAI rejected the request. Please try another photo.',
        'server' => 'OpenAI is having a temporary problem. Please try again shortly.',
        'unknown' => 'OpenAI error (:status).',
    ],

];

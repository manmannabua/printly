<?php

return [
    'enabled' => env('RESUME_EXTRACTION_ENABLED', false),
    'api_key' => env('RESUME_EXTRACTION_API_KEY'),
    'model' => env('RESUME_EXTRACTION_MODEL', 'claude-sonnet-4-20250514'),
    'max_tokens' => 4096,
    'timeout' => 30,
    'max_text_length' => 50000,
    'memory_limit' => '256M',
];

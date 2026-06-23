<?php

return [
    'memory_limit'    => env('MANUAL_PARSING_MEMORY_LIMIT', '512M'),
    'max_pdf_size_mb' => (int) env('MANUAL_MAX_PDF_SIZE_MB', 50),

    // AI-powered parsing
    'ai_enabled'      => env('MANUAL_PARSING_AI_ENABLED', false),
    'api_key'         => env('MANUAL_PARSING_API_KEY'),
    'model'           => env('MANUAL_PARSING_MODEL', 'claude-sonnet-4-6'),
    'max_tokens'      => (int) env('MANUAL_PARSING_MAX_TOKENS', 0),   // 0 = auto-calculated per chunk
    'timeout'         => (int) env('MANUAL_PARSING_TIMEOUT', 60),
    'max_text_length' => (int) env('MANUAL_PARSING_MAX_TEXT_LENGTH', 80000),
    'pages_per_group' => (int) env('MANUAL_PARSING_PAGES_PER_GROUP', 5),
];

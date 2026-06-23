<?php

return [
    'enabled'          => env('GEMINI_RESUME_ENABLED', false),
    'chat_enabled'     => env('GEMINI_CHAT_ENABLED', false),
    'conflict_enabled' => env('GEMINI_LEAVE_CONFLICT_ENABLED', false),
    'api_key'          => env('GEMINI_API_KEY'),
    'model'            => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    'max_tokens'       => 8192,
    'timeout'          => 45,
];

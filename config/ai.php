<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI provider for photo analysis (and future outfit AI)
    |--------------------------------------------------------------------------
    |
    | Supported: gemini, openrouter. Leave provider empty to use the local
    | colour heuristic only (no external API call).
    |
    */

    'provider' => env('AI_PROVIDER'),

    'model' => env('AI_MODEL', 'gemini-3.8-flash'),

    'timeout' => (int) env('AI_TIMEOUT', 20),

    'daily_limit' => (int) env('AI_DAILY_LIMIT', 20),

    'cache_ttl' => (int) env('AI_CACHE_TTL', 600),

    'keys' => [
        'gemini' => env('GEMINI_API_KEY'),
        'groq' => env('GROQ_API_KEY'),
        'openrouter' => env('OPENROUTER_API_KEY'),
    ],

    'gemini' => [
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
    ],

    'openrouter' => [
        'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
        // Vision-capable free/cheap default; override with AI_MODEL.
        'default_model' => 'google/gemini-3.8-flash',
    ],

];

<?php

/*
|--------------------------------------------------------------------------
| Stylist configuration
|--------------------------------------------------------------------------
|
| Tunable numbers for the fit, silhouette and outfit scoring engines live
| here rather than being hard-coded inside services.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Cut tags
    |--------------------------------------------------------------------------
    |
    | The allowed cut tags for a garment. Kept in one place so the wardrobe
    | form, the validation rules and the silhouette engine cannot drift.
    |
    */

    'cut_tags' => [
        'structured_shoulder',
        'dropped_shoulder',
        'high_waist',
        'mid_waist',
        'low_waist',
        'a_line',
        'straight',
        'wide_leg',
        'slim_leg',
        'cropped',
        'longline',
        'oversized',
        'fitted',
        'relaxed',
        'pleated',
        'wrap',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fit rules (Phase 3)
    |--------------------------------------------------------------------------
    |
    | Per-pair ease ranges in cm, stretch allowances and which body/garment
    | measurement pairs apply to each category.
    |
    */

    'fit' => [
        // 'ease' => [],
        // 'stretch' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Silhouette rules (Phase 3)
    |--------------------------------------------------------------------------
    |
    | Body shape labels, their evaluation order, and the cut tags each shape
    | favours or avoids.
    |
    */

    'silhouette' => [
        // 'labels' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Outfit scoring (Phase 4)
    |--------------------------------------------------------------------------
    |
    | Factor weights, thresholds and colour/pattern penalties.
    |
    */

    'scoring' => [
        // 'weights' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI mode (Phase 5)
    |--------------------------------------------------------------------------
    |
    | Fallback behaviour for the AI generator. Secrets live in config/ai.php
    | and .env only; never put a key in this file.
    |
    */

    'ai' => [
        // 'fallback_on' => ['timeout', 'rate_limited', 'invalid_response'],
    ],

];

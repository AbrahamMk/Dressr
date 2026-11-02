<?php

/*
|--------------------------------------------------------------------------
| Stylist configuration
|--------------------------------------------------------------------------
|
| Tunable numbers for the fit, silhouette and outfit scoring engines live
| here rather than being hard-coded inside services.
|
| PROJECT_BRIEF.md is not in this repository; these defaults follow common
| garment-ease practice and the phase rules in rules/rules.md. Retune freely.
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
    | Garment chest/waist/hip fields are flat widths (cm); ease is computed as
    | (width * 2) - body circumference. Shoulder and inseam compare directly.
    |
    */

    'fit' => [
        'pairs' => [
            'chest' => [
                'body' => 'chest_cm',
                'garment' => 'g_chest_width',
                'mode' => 'circumference',
            ],
            'waist' => [
                'body' => 'waist_cm',
                'garment' => 'g_waist_width',
                'mode' => 'circumference',
            ],
            'hips' => [
                'body' => 'hips_cm',
                'garment' => 'g_hip_width',
                'mode' => 'circumference',
            ],
            'shoulder' => [
                'body' => 'shoulder_width_cm',
                'garment' => 'g_shoulder_width',
                'mode' => 'width',
            ],
            'inseam' => [
                'body' => 'inseam_cm',
                'garment' => 'g_inseam',
                'mode' => 'length',
            ],
        ],

        'category_pairs' => [
            'top' => ['chest', 'shoulder'],
            'bottom' => ['waist', 'hips', 'inseam'],
            'dress' => ['chest', 'waist', 'hips'],
            'outerwear' => ['chest', 'shoulder'],
            'shoes' => [],
            'accessory' => [],
        ],

        /*
         * Ideal and acceptable ease ranges in cm (after stretch allowance is
         * applied to the lower bound). Values are [min, max].
         */
        'ease' => [
            'chest' => [
                'good' => [4.0, 12.0],
                'acceptable' => [2.0, 18.0],
            ],
            'waist' => [
                'good' => [2.0, 8.0],
                'acceptable' => [0.0, 14.0],
            ],
            'hips' => [
                'good' => [2.0, 10.0],
                'acceptable' => [0.0, 16.0],
            ],
            'shoulder' => [
                'good' => [-1.0, 2.0],
                'acceptable' => [-2.5, 4.0],
            ],
            'inseam' => [
                'good' => [-2.0, 2.0],
                'acceptable' => [-4.0, 4.0],
            ],
        ],

        /*
         * Centimetres added to the lower bound of each ease range so stretchy
         * fabric can sit closer to the body without being labelled tight.
         */
        'stretch_allowance' => [
            'none' => 0.0,
            'some' => 2.0,
            'high' => 4.0,
        ],

        /*
         * Outerwear is meant to layer; widen every ease range by this amount.
         */
        'outerwear_extra_ease' => 4.0,

        'skip_inseam_subcategories' => [
            'shorts',
            'short',
            'skirt',
            'skirts',
            'skort',
            'skorts',
            'cropped',
            'crop',
        ],

        'skip_inseam_cut_tags' => [
            'cropped',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Silhouette rules (Phase 3)
    |--------------------------------------------------------------------------
    */

    'silhouette' => [
        'evaluation_order' => [
            'hourglass',
            'pear',
            'inverted_triangle',
            'apple',
            'rectangle',
        ],

        /*
         * Ratios used while classifying. bust≈chest_cm.
         */
        'thresholds' => [
            // |chest - hips| / avg(chest, hips) must be below this for hourglass balance
            'balanced_diff' => 0.05,
            // waist must be at most this fraction of the smaller of chest/hips
            'defined_waist' => 0.75,
            // hips noticeably larger than chest
            'pear_hips_ratio' => 1.05,
            // chest noticeably larger than hips
            'inverted_chest_ratio' => 1.05,
            // waist close to or larger than hips (soft middle)
            'apple_waist_hips' => 0.90,
            // rectangle: all three within this relative band of each other
            'rectangle_band' => 0.08,
        ],

        'shapes' => [
            'hourglass' => [
                'label' => 'Hourglass',
                'explanation' => 'Balanced chest and hips with a defined waist. Fitted and wrap styles usually flatter.',
                'favored' => ['fitted', 'high_waist', 'wrap', 'a_line', 'belted'],
                'avoided' => ['oversized', 'dropped_shoulder', 'boxy'],
            ],
            'pear' => [
                'label' => 'Pear',
                'explanation' => 'Hips are fuller than the upper body. Structured shoulders and A-line bottoms balance the silhouette.',
                'favored' => ['structured_shoulder', 'a_line', 'wide_leg', 'high_waist', 'fitted'],
                'avoided' => ['slim_leg', 'low_waist', 'tight_hip'],
            ],
            'inverted_triangle' => [
                'label' => 'Inverted triangle',
                'explanation' => 'Broader upper body relative to the hips. Softer shoulders and volume at the hip balance the frame.',
                'favored' => ['dropped_shoulder', 'wide_leg', 'a_line', 'pleated', 'relaxed'],
                'avoided' => ['structured_shoulder', 'slim_leg', 'fitted'],
            ],
            'apple' => [
                'label' => 'Apple',
                'explanation' => 'Fuller midsection with less waist definition. Length and soft structure draw the eye vertically.',
                'favored' => ['longline', 'a_line', 'wrap', 'relaxed', 'straight'],
                'avoided' => ['high_waist', 'fitted', 'crop'],
            ],
            'rectangle' => [
                'label' => 'Rectangle',
                'explanation' => 'Chest, waist and hips are similar. Cuts that create shape or add soft volume help.',
                'favored' => ['high_waist', 'wrap', 'pleated', 'a_line', 'belted', 'fitted'],
                'avoided' => ['straight', 'oversized'],
            ],
        ],

        /*
         * Item silhouette score: start at 0.5, +favored_weight per matching tag,
         * -avoided_weight per matching tag, clamped to 0..1.
         */
        'item_score' => [
            'base' => 0.5,
            'favored_weight' => 0.15,
            'avoided_weight' => 0.15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Outfit scoring (Phase 4)
    |--------------------------------------------------------------------------
    */

    'scoring' => [
        'weights' => [
            'color' => 0.25,
            'formality' => 0.20,
            'season' => 0.15,
            'fit' => 0.20,
            'silhouette' => 0.10,
            'variety' => 0.10,
        ],

        'occasions' => [
            'casual' => [
                'label' => 'Casual',
                'formality' => [1, 3],
            ],
            'smart_casual' => [
                'label' => 'Smart casual',
                'formality' => [2, 4],
            ],
            'business' => [
                'label' => 'Business',
                'formality' => [3, 5],
            ],
            'formal' => [
                'label' => 'Formal',
                'formality' => [4, 5],
            ],
            'athletic' => [
                'label' => 'Athletic',
                'formality' => [1, 2],
            ],
        ],

        'temperatures' => [
            'hot' => [
                'label' => 'Hot',
                'outerwear' => 'exclude',
            ],
            'mild' => [
                'label' => 'Mild',
                'outerwear' => 'optional',
            ],
            'cold' => [
                'label' => 'Cold',
                'outerwear' => 'prefer',
            ],
        ],

        'default_count' => 5,
        'max_count' => 10,
        'max_combinations' => 2000,

        'color' => [
            'max_chromatic_before_penalty' => 3,
            'over_color_penalty' => 0.15,
            'pattern_clash_penalty' => 0.25,
            'busy_patterns' => ['plaid', 'floral', 'graphic', 'striped'],
            // Hue distance (degrees) thresholds for harmony detection
            'analogous_max' => 40,
            'complementary_tolerance' => 30,
            'triadic_tolerance' => 30,
        ],

        'fit_label_scores' => [
            'good_fit' => 1.0,
            'acceptable' => 0.7,
            'tight' => 0.25,
            'loose' => 0.35,
            'unknown' => 0.55,
        ],

        /*
         * Variety (Phase 6): penalise recently worn pieces and exact repeats.
         */
        'variety' => [
            'lookback_days' => 14,
            // Subtracted once per distinct recently-worn item in the outfit.
            'item_penalty' => 0.12,
            // Extra hit when the exact same item-id set was worn in the window.
            'repeat_combo_penalty' => 0.35,
            // Floor so variety never goes fully to zero.
            'min_score' => 0.15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI mode (Phase 5) — left empty on purpose
    |--------------------------------------------------------------------------
    */

    'ai' => [
        // 'fallback_on' => ['timeout', 'rate_limited', 'invalid_response'],
    ],

];

<?php

declare(strict_types=1);

return [
    'solar' => [
        [
            'slug' => 'international_new_year',
            'month' => 1,
            'day' => 1,
            'type' => 'public',
            'name_key' => 'international_new_year',
        ],
        [
            'slug' => 'victory_over_genocide_regime',
            'month' => 1,
            'day' => 7,
            'type' => 'public',
            'name_key' => 'victory_over_genocide_regime',
        ],
        [
            'slug' => 'international_womens_day',
            'month' => 3,
            'day' => 8,
            'type' => 'public',
            'name_key' => 'international_womens_day',
        ],
        [
            'slug' => 'king_fathers_memorial',
            'month' => 10,
            'day' => 15,
            'type' => 'public',
            'name_key' => 'king_fathers_memorial',
        ],
    ],
    'lunisolar' => [
        [
            'slug' => 'khmer_new_year',
            'resolver' => 'khmer_new_year',
            'type' => 'public',
            'name_key' => 'khmer_new_year',
        ],
        [
            'slug' => 'visak_bochea',
            'resolver' => 'lunar_phase',
            'month_slug' => 'visak',
            'phase' => 'waxing',
            'day' => 15,
            'type' => 'public',
            'name_key' => 'visak_bochea',
        ],
    ],
];


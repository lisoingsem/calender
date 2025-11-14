<?php

declare(strict_types=1);

return [
    'solar' => [
        [
            'slug' => 'international_new_year',
            'month' => 1,
            'day' => 1,
            'type' => 'public',
            'title' => 'international_new_year',
        ],
        [
            'slug' => 'victory_over_genocide_regime',
            'month' => 1,
            'day' => 7,
            'type' => 'public',
            'title' => 'victory_over_genocide_regime',
        ],
        [
            'slug' => 'international_womens_day',
            'month' => 3,
            'day' => 8,
            'type' => 'public',
            'title' => 'international_womens_day',
        ],
        [
            'slug' => 'king_fathers_memorial',
            'month' => 10,
            'day' => 15,
            'type' => 'public',
            'title' => 'king_fathers_memorial',
        ],
    ],
    'lunisolar' => [
        [
            'slug' => 'khmer_new_year',
            'resolver' => 'khmer_new_year',
            'type' => 'public',
            'title' => 'khmer_new_year',
        ],
        [
            'slug' => 'visak_bochea',
            'resolver' => 'lunar_phase',
            'month_slug' => 'visak',
            'phase' => 'waxing',
            'day' => 15,
            'type' => 'public',
            'title' => 'visak_bochea',
        ],
    ],
];


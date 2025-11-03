<?php

return [
    

    'credit_conversion_factor' => env('BANKING_CCF', 0.75),
    'capital_requirement_pct' => env('BANKING_CAPITAL_REQ_PCT', 0.08),
    'tier1_capital' => env('BANKING_TIER1_CAPITAL', 50000000.00),
    'tier2_capital' => env('BANKING_TIER2_CAPITAL', 25000000.00),

    'display_precision_amount' => env('BANKING_PRECISION_AMOUNT', 2),
    'display_precision_pct' => env('BANKING_PRECISION_PCT', 4),

    'raroc_cap_floor' => env('BANKING_RAROC_CAP_FLOOR', 0.0001),
    'base_currency' => env('BANKING_BASE_CURRENCY', 'USD'),

    'default_risk_weights' => [
        'sovereign' => [
            'AAA' => 0,
            'AA' => 0,
            'A' => 20,
            'BBB' => 50,
            'BB' => 100,
            'B' => 150,
            'CCC' => 150,
            'unrated' => 100
        ],
        'corporate' => [
            'AAA' => 20,
            'AA' => 20,
            'A' => 50,
            'BBB' => 100,
            'BB' => 150,
            'B' => 150,
            'CCC' => 150,
            'unrated' => 100
        ],
        'retail' => [
            'secured' => 35,
            'unsecured' => 75
        ]
    ],

    'default_pd_values' => [
        'AAA' => 0.0001,
        'AA' => 0.0003,
        'A' => 0.0010,
        'BBB' => 0.0030,
        'BB' => 0.0150,
        'B' => 0.0500,
        'CCC' => 0.1500,
        'unrated' => 0.0300
    ],

    'default_lgd_values' => [
        'cash' => 0.00,
        'government_securities' => 0.05,
        'real_estate' => 0.20,
        'receivables' => 0.25,
        'inventory' => 0.45,
        'equipment' => 0.60,
        'unsecured' => 0.75
    ],

    'stress_scenarios' => [
        'base_case' => [
            'pd_multiplier' => 1.0,
            'lgd_multiplier' => 1.0,
            'description' => 'Current economic conditions'
        ],
        'mild_stress' => [
            'pd_multiplier' => 1.5,
            'lgd_multiplier' => 1.2,
            'description' => 'Mild economic downturn'
        ],
        'moderate_stress' => [
            'pd_multiplier' => 2.0,
            'lgd_multiplier' => 1.3,
            'description' => 'Moderate recession'
        ],
        'severe_stress' => [
            'pd_multiplier' => 3.0,
            'lgd_multiplier' => 1.5,
            'description' => 'Severe economic crisis'
        ]
    ],

    'maturity_buckets' => [
        '0-3m' => ['min' => 0, 'max' => 3],
        '3-6m' => ['min' => 3, 'max' => 6],
        '6-12m' => ['min' => 6, 'max' => 12],
        '1-2y' => ['min' => 12, 'max' => 24],
        '2-5y' => ['min' => 24, 'max' => 60],
        '5y+' => ['min' => 60, 'max' => null]
    ],

    'par_thresholds' => [
        'par30' => 30,
        'par60' => 60,
        'par90' => 90,
        'par180' => 180
    ],

    'ifrs9_stages' => [
        'stage1' => [
            'description' => 'Performing loans (12-month ECL)',
            'days_past_due_max' => 29,
            'significant_increase_criteria' => false
        ],
        'stage2' => [
            'description' => 'Underperforming loans (lifetime ECL)',
            'days_past_due_min' => 30,
            'days_past_due_max' => 89,
            'significant_increase_criteria' => true
        ],
        'stage3' => [
            'description' => 'Non-performing loans (lifetime ECL)',
            'days_past_due_min' => 90,
            'credit_impaired' => true
        ]
    ]
];



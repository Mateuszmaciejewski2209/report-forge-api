<?php

return [

    'free' => [
        'price_pln' => 0,
        'price_usd' => 0,
        'reports_limit' => 5,
        'max_upload_mb' => 10,
        'storage_mb' => 10,
        'ai_credits' => 100,
    ],

    'pro' => [
        'price_pln' => 119,
        'price_usd' => 29,
        'reports_limit' => null,
        'max_upload_mb' => 100,
        'storage_mb' => 102_400,
        'ai_credits' => 5000,
    ],

    'business' => [
        'price_pln' => 399,
        'price_usd' => 99,
        'reports_limit' => null,
        'max_upload_mb' => 1024,
        'storage_mb' => 1_048_576,
        'ai_credits' => 50_000,
    ],

];

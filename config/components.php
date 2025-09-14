<?php

return [

    'api_url' => env('COMPONENTS_API_URL', 'https://components.lucasvanbriemen.nl/api'),

    'cache_ttl' => env('COMPONENTS_CACHE_TTL', 86400),

    'version' => env('COMPONENTS_VERSION', '1.0.0'),

    'fallback_enabled' => env('COMPONENTS_FALLBACK_ENABLED', true),

    'available_components' => [
        'pagination',
        'modal',
        'dropdown',
        'alert',
        'card',
        'button',
        'form',
        'table',
        'tabs',
        'accordion',
        'breadcrumb',
        'badge',
        'spinner',
        'tooltip',
        'toast'
    ],

    'auto_load' => [
        'pagination'
    ],

    'cache_driver' => env('COMPONENTS_CACHE_DRIVER', 'file'),

    'retry' => [
        'times' => 3,
        'sleep' => 100,
    ],

    'timeout' => 10,

    'middleware' => [
        'api' => ['api'],
        'web' => ['web']
    ],

    'rate_limit' => [
        'enabled' => env('COMPONENTS_RATE_LIMIT', false),
        'attempts' => 60,
        'decay_minutes' => 1
    ],

    'logging' => [
        'enabled' => env('COMPONENTS_LOGGING', true),
        'channel' => env('COMPONENTS_LOG_CHANNEL', 'stack')
    ],

    'assets' => [
        'inline' => env('COMPONENTS_INLINE_ASSETS', false),
        'minify' => env('COMPONENTS_MINIFY', true),
        'versioning' => env('COMPONENTS_ASSET_VERSIONING', true)
    ],

    'security' => [
        'validate_response' => true,
        'sanitize_html' => false,
        'allowed_tags' => [],
        'strip_scripts' => false
    ]
];
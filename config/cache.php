<?php

return [

    'default' => env('CACHE_DRIVER', 'file'),

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => env('DB_CONNECTION'),
            'lock_connection' => env('CACHE_LOCK_CONNECTION'),
            'lock_lottery' => [2, 100],
            'prefix' => '',
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_connection' => env('CACHE_LOCK_CONNECTION'),
            'lock_lottery' => [2, 100],
            'prefix' => '',
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => class_exists(Memcached::class) ? [
                Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ] : [],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
            'lock_connection' => env('CACHE_LOCK_CONNECTION'),
            'lock_lottery' => [2, 100],
            'prefix' => '',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => env('CACHE_LOCK_CONNECTION'),
            'lock_lottery' => [2, 100],
            'prefix' => '',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    'prefix' => env('CACHE_PREFIX', 'outreach_crm_'),

];
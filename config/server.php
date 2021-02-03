<?php
/**
 * Tus configuration.
 */
return [
    /**
     * Redis connection parameters.
     */
    'redis' => [
        'host' => env('TUS_REDIS_HOST', '127.0.0.1'),
        'port' => env('TUS_REDIS_PORT', '6379'),
        'database' => env('TUS_REDIS_DB', 0),
    ],

    /**
     * File cache configs.
     */
    'file' => [
        'dir' => '/home/bato/ws/github/bedita/bedita4/tmp/',
        'name' => 'tus_php.server.cache',
    ],
];

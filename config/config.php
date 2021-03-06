<?php
/**
 * Tus configuration.
 */
return [
    'Tus' => [
        /*
         * Configured base endpoint
         */
        'endpoint' => env('TUS_ENDPOINT', 'tus'),

        /*
         * BEdita Filesystem conf to use to save files.
         */
        'filesystem' => env('TUS_FILESYSTEM', 'tus'),

        /*
         * Upload directory of filesystem configured.
         */
        'uploadDir' => env('TUS_UPLOAD_DIR', 'uploads'),

        /*
         * Cache adapter used by tus server.
         * It can be (see 'server' keys):
         * - file
         * - redis
         */
        'cache' => env('TUS_CACHE_ENGINE', 'file'),

        /*
         * Tus server cache configurations
         */
        'server' => [
            /**
             * Redis connection parameters.
             */
            'redis' => [
                'host' => env('TUS_REDIS_HOST', '127.0.0.1'),
                'port' => env('TUS_REDIS_PORT', '6379'),
                'database' => env('TUS_REDIS_DB', 0),
            ],

            /*
             * File cache configs.
             */
            'file' => [
                'dir' => env('TUS_CACHE_DIR', TMP),
                'name' => env('TUS_CACHE_FILE', 'tus_php.server.cache'),
            ],
        ],

        /*
         * Trusted Proxies config.
         */
        'trustedProxies' => [
            'proxies' => env('TUS_TRUSTED_PROXIES', '*'),
            'headers' => env('TUS_TRUSTED_HEADERS'),
        ],

        /*
         * Headers configuration:
         *
         * - `exclude` => headers that Tus server shouldn't set.
         *                Can be an array or a comma separated string.
         */
        'headers' => [
            'exclude' => env('TUS_HEADERS_EXCLUDE'),
        ],
    ],
];

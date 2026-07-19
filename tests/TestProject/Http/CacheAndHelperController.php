<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Support\Facades\Cache;

/**
 * Tests for the `Cache::remember()` family and the callback-based helpers
 * (`rescue`, `retry`, `tap`, `value`, `with`).
 */
class CacheAndHelperController
{
    /**
     * Cache::remember callback return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'Cache::remember callback return value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'cached' => [
                                    'type' => 'boolean',
                                ],
                                'ttl' => [
                                    'type' => 'integer',
                                    'const' => 60,
                                ],
                            ],
                            'required' => [
                                'cached',
                                'ttl',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function cacheRemember(): mixed
    {
        return Cache::remember('planets', 60, fn () => [
            'cached' => true,
            'ttl' => 60,
        ]);
    }


    /**
     * cache()->remember callback return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'cache()->remember callback return value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'fresh' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'fresh',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function cacheHelperRemember(): mixed
    {
        return cache()->remember('planets', 60, fn () => [
            'fresh' => true,
        ]);
    }


    /**
     * Cache::rememberForever callback return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'Cache::rememberForever callback return value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'forever' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'forever',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function cacheRememberForever(): mixed
    {
        return Cache::rememberForever('planets', fn () => [
            'forever' => true,
        ]);
    }


    /**
     * cache()->sear callback return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'cache()->sear callback return value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'seared' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'seared',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function cacheHelperSear(): mixed
    {
        return cache()->sear('planets', fn () => [
            'seared' => true,
        ]);
    }


    /**
     * rescue callback return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'rescue callback return value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'rescued' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'rescued',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function rescueHelper(): mixed
    {
        return rescue(fn () => [
            'rescued' => true,
        ]);
    }


    /**
     * retry callback return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'retry callback return value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'attempts' => [
                                    'type' => 'integer',
                                    'const' => 3,
                                ],
                            ],
                            'required' => [
                                'attempts',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function retryHelper(): mixed
    {
        return retry(3, fn () => [
            'attempts' => 3,
        ]);
    }


    /**
     * tap returns the passed-through value
     */
    #[ExpectedOperationSchema([
        'summary' => 'tap returns the passed-through value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'tapped' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'tapped',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function tapHelper(): mixed
    {
        return tap(['tapped' => true], fn (array $value) => [
            'ignored' => 1,
        ]);
    }


    /**
     * value resolves a closure argument
     */
    #[ExpectedOperationSchema([
        'summary' => 'value resolves a closure argument',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'valued' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'valued',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function valueHelper(): mixed
    {
        return value(fn () => [
            'valued' => true,
        ]);
    }


    /**
     * with passes the value through a callback
     */
    #[ExpectedOperationSchema([
        'summary' => 'with passes the value through a callback',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'withed' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'withed',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function withHelper(): mixed
    {
        return with(['base' => 1], fn (array $value) => [
            'withed' => true,
        ]);
    }
}

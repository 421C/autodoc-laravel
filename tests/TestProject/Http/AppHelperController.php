<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use AutoDoc\Laravel\Tests\TestProject\Services\PlanetService;

/**
 * Tests for the `app()` container helper.
 */
class AppHelperController
{
    /**
     * Model resolved via app()
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model resolved via app()',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'name' => [
                                    'type' => 'string',
                                ],
                                'diameter' => [
                                    'type' => 'number',
                                    'format' => 'float',
                                ],
                                'visited' => [
                                    'type' => 'boolean',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'updated_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'diameter',
                                'visited',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelResolvedFromApp(): mixed
    {
        return app(Planet::class);
    }


    /**
     * Method call on a service resolved via app()
     */
    #[ExpectedOperationSchema([
        'summary' => 'Method call on a service resolved via app()',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'visited' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'name',
                                'visited',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function methodCallOnResolvedService(): mixed
    {
        return app(PlanetService::class)->getSummary();
    }


    /**
     * Method call on the application returned by app()
     */
    #[ExpectedOperationSchema([
        'summary' => 'Method call on the application returned by app()',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'text/plain' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function methodCallOnApplication(): mixed
    {
        return app()->getLocale();
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;

/**
 * Tests for request parameters: headers, query params.
 */
class RequestParamsController
{
    /**
     * Request header parameter
     *
     * @request-header planet-id {description: 'Planet ID'}
     */
    #[ExpectedOperationSchema([
        'summary' => 'Request header parameter',
        'description' => '',
        'parameters' => [
            [
                'in' => 'header',
                'name' => 'planet-id',
                'description' => 'Planet ID',
                'schema' => [
                    'type' => 'string',
                ],
            ],
        ],
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
                                    'const' => 1,
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
    public function headerParameter(): mixed
    {
        $planet = new Planet;

        $planet->id = 1;

        return $planet;
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'param1',
                'required' => true,
                'schema' => [
                    'allOf' => [
                        [
                            'type' => 'string',
                        ],
                        [
                            'type' => 'number',
                        ],
                    ],
                ],
            ],
            [
                'in' => 'query',
                'name' => 'param2',
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ],
    ])]
    public function multipleValidateCalls(): void
    {
        request()->validate([
            'param1' => 'required|string',
        ]);

        request()->validate([
            'param1' => 'numeric',
            'param2' => 'array',
        ]);
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'token',
                'schema' => [
                    'type' => 'string',
                ],
            ],
        ],
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => [
                                    'string',
                                    'boolean',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function queryParamWithMutation(): mixed
    {
        $tokens = request()->query('token');

        $tokens['required'] = true;

        return $tokens;
    }


    /**
     * @request-query user_id {type: int}
     */
    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'user_id',
                'schema' => [
                    'type' => 'integer',
                ],
            ],
        ],
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'anyOf' => [
                                    [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    [
                                        'type' => 'string',
                                    ],
                                    [
                                        'type' => 'null',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function phpdocQueryParam(): mixed
    {
        return [
            request()->query('user_id'),
            request()->query('user_id'),
            request()->query('user_id'),
        ];
    }
}

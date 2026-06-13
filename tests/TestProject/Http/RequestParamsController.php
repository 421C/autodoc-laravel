<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\StateEnum;
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
                    'type' => 'string',
                    'format' => 'numeric',
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


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'header',
                'name' => 'Authorization',
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
                            'type' => [
                                'string',
                                'null',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function authorizationHeader(): mixed
    {
        $header = request()->header('Authorization');

        if (is_string($header)) {
            return $header;
        }

        return null;
    }


    #[ExpectedOperationSchema([
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'tags' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'states' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'integer',
                                    'description' => '[StateEnum](#/schemas/StateEnum)',
                                    'enum' => [
                                        1,
                                        2,
                                    ],
                                ],
                            ],
                            'name' => [
                                'type' => 'string',
                            ],
                            'active' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'tags' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'states' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'integer',
                                        'description' => '[StateEnum](#/schemas/StateEnum)',
                                        'enum' => [
                                            1,
                                            2,
                                        ],
                                    ],
                                ],
                                'subset' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                        ],
                                        'active' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                            'required' => [
                                'tags',
                                'states',
                                'subset',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function typedParameterHelpers(): mixed
    {
        return [
            'tags' => request()->collect('tags'),
            'states' => request()->enums('states', StateEnum::class),
            'subset' => request()->array(['name', 'active']),
        ];
    }


    #[ExpectedOperationSchema([
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'address' => [
                                'type' => 'object',
                                'properties' => [
                                    'city' => [
                                        'type' => 'string',
                                    ],
                                    'zip' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                            'items' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'active' => [
                                            'type' => 'boolean',
                                        ],
                                    ],
                                ],
                            ],
                            'user' => [
                                'type' => 'object',
                                'properties' => [
                                    'age' => [
                                        'type' => 'integer',
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
    ])]
    public function dotNotationParameters(): void
    {
        // Dot-notation keys map to nested request shapes; `*` segments describe
        // array-of-object input; array([...]) key lists merge siblings under a
        // shared parent. Separate calls sharing a parent (user.*) deep-merge
        // into one object. Mirrors Laravel's data_get/Arr::set semantics.
        request()->string('user.name');
        request()->integer('user.age');
        request()->boolean('items.*.active');
        request()->array(['address.city', 'address.zip']);
    }
}

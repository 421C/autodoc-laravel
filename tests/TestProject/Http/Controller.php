<?php

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\StateEnum;
use AutoDoc\Laravel\Tests\TestProject\Entities\User;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserCollection;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResource;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;

/**
 * @phpstan-type Symbol 'a'|'b'|'c'
 */
class Controller
{
    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'count' => [
                                'minimum' => 0,
                                'type' => [
                                    'integer',
                                    'null',
                                ],
                            ],
                            'symbol' => [
                                'type' => 'string',
                                'enum' => [
                                    'a',
                                    'b',
                                    'c',
                                ],
                            ],
                            'number' => [
                                'type' => 'integer',
                                'enum' => [
                                    0,
                                    10,
                                    20,
                                ],
                            ],
                        ],
                        'required' => [
                            'name',
                        ],
                    ],
                ],
            ],
            'description' => '',
            'required' => false,
        ],
        'responses' => [],
    ])]
    public function route1(Request $request): void
    {
        $request->validate([
            'name' => 'required',
            'count' => 'integer|nullable|min:0',
            'symbol' => 'in:a,b,c',
            'number' => 'in:0,10,20|integer',
        ]);
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'child.records' => [
                                'additionalProperties' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'date' => [
                                            'type' => 'string',
                                        ],
                                        'symbol' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'a',
                                                'b',
                                                'c',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'date',
                                        'symbol',
                                    ],
                                ],
                                'description' => 'This parameter reads type from `@var` tag.',
                                'type' => 'object',
                            ],
                            'records' => [
                                'type' => 'array',
                                'description' => 'Records description',
                                'items' => [
                                    'properties' => [
                                        'created_at' => [
                                            'type' => 'string',
                                            'format' => 'date',
                                            'description' => 'Description of the `created_at` parameter.',
                                            'examples' => [
                                                '\'2024-10-20\'',
                                            ],
                                        ],
                                        'symbol' => [
                                            'description' => 'This parameter reads type from `@var` tag.',
                                            'enum' => [
                                                'a',
                                                'b',
                                                'c',
                                            ],
                                            'type' => 'string',
                                        ],
                                    ],
                                    'type' => 'object',
                                ],
                            ],
                        ],
                        'required' => [
                            'child.records',
                        ],
                    ],
                ],
            ],
            'description' => '',
            'required' => false,
        ],
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'anyOf' => [
                                [
                                    'type' => 'string',
                                    'format' => 'date-time',
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function route2(): mixed
    {
        request()->validate([
            /**
             * Records description
             */
            'records' => 'array',

            /**
             * This parameter reads type from `@var` tag.
             * 
             * @var Symbol
             */
            'records.*.symbol' => 'sometimes',

            /**
             * Description of the `created_at` parameter.
             * 
             * @example '2024-10-20'
             */
            'records.*.created_at' => 'date',

            /**
             * This parameter reads type from `@var` tag.
             * 
             * @var array<string, array{
             *     date: string,
             *     symbol: Symbol,
             * }>
             */
            'child\.records' => 'required',
        ]);

        $potentialReturnType = [];

        while (! $potentialReturnType) {
            $potentialReturnType = rand(0, 50);
        }

        if ($potentialReturnType === 14) {
            $potentialReturnType = now();
        }

        return $potentialReturnType;
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'client_id' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                                'format' => 'uuid',
                            ],
                            'symbols' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'status' => [
                                'type' => 'integer',
                                'description' => '[StateEnum](#/schemas/StateEnum)',
                                'enum' => [
                                    1,
                                    2,
                                ],
                            ],
                            'position' => [
                                'type' => 'object',
                                'properties' => [
                                    'x' => [
                                        'type' => 'string',
                                        'enum' => [
                                            1,
                                            2,
                                            3,
                                            '*',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'description' => '',
            'required' => false,
        ],
        'responses' => [],
    ])]
    public function route3(Request $request): void
    {
        $validationRules = [
            'client_id' => 'required',
            'symbols' => [new ArrayRule()],
        ];

        $validationRules['client_id'] = 'nullable|uuid';

        $validationRules['status'][] = new Enum(StateEnum::class);

        $validationRules['position.x'] = [
            new In([1, 2, 3, '*']),
        ];

        $request->validate($validationRules);
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'r4_status' => [
                                'type' => 'integer',
                                'description' => '[StateEnum](#/schemas/StateEnum)',
                                'enum' => [
                                    1,
                                    2,
                                ],
                            ],
                            'r4_number' => [
                                'type' => 'number',
                                'description' => 'Testing array destructuring.',
                                'enum' => [
                                    0,
                                    -0.1,
                                ]
                            ]
                        ],
                    ],
                ],
            ],
            'description' => '',
            'required' => false,
        ],
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                    'description' => '[StateEnum](#/schemas/StateEnum)',
                                    'enum' => [
                                        1,
                                        2,
                                    ],
                                ],
                                'n' => [
                                    'type' => 'number',
                                    'format' => 'float',
                                    'enum' => [
                                        115,
                                        -0.1,
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route4(FormRequest $request): mixed
    {
        $enumRule = Rule::enum('AutoDoc\Laravel\Tests\TestProject\Entities\StateEnum');

        $n = 1000;
        $n = -0.1;
        $allowedNumbers = [0, $n];

        $request->validate([
            'r4_status' => $enumRule,

            /**
             * Testing array destructuring.
             */
            'r4_number' => Rule::in([
                ...$allowedNumbers,
            ]),
        ]);

        if ($request->r4_number) {
            $n = 115.0;
        }

        return response()->json([
            'status' => $request->enum('status', StateEnum::class),
            'n' => $n,
        ]);
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'items' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'object',
                                        ],
                                        'id' => [
                                            'type' => 'integer',
                                        ],
                                    ],
                                    'required' => [
                                        'data',
                                    ],
                                ],
                            ],
                        ],
                        'required' => [
                            'items',
                        ],
                    ],
                ],
            ],
            'description' => '',
            'required' => false,
        ],
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => [
                                'integer',
                                'null',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route5(CustomRequest $request): mixed
    {
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
        return response()->json($request->items[0]['id'] ?? null);
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => null,
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'created_at' => [
                                    'format' => 'date-time',
                                    'type' => 'string',
                                ],
                                'email' => [
                                    'type' => 'string',
                                ],
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'name' => [
                                    'type' => 'string',
                                ],
                                'status' => [
                                    'description' => '[StateEnum](#/schemas/StateEnum)',
                                    'enum' => [
                                        1,
                                        2,
                                    ],
                                    'type' => 'integer',
                                ],
                                'updated_at' => [
                                    'format' => 'date-time',
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route6(): mixed
    {
        return new UserResource(new User);
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => null,
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'format' => 'date-time',
                                                'type' => 'string',
                                            ],
                                            'email' => [
                                                'type' => 'string',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'status' => [
                                                'description' => '[StateEnum](#/schemas/StateEnum)',
                                                'enum' => [
                                                    1,
                                                    2,
                                                ],
                                                'type' => 'integer',
                                            ],
                                            'updated_at' => [
                                                'format' => 'date-time',
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route7(): mixed
    {
        return UserResource::collection([new User]);
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => null,
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'users' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'format' => 'date-time',
                                                'type' => 'string',
                                            ],
                                            'email' => [
                                                'type' => 'string',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'status' => [
                                                'description' => '[StateEnum](#/schemas/StateEnum)',
                                                'enum' => [
                                                    1,
                                                    2,
                                                ],
                                                'type' => 'integer',
                                            ],
                                            'updated_at' => [
                                                'format' => 'date-time',
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'count' => [
                                    'description' => 'Number of users returned.',
                                    'type' => 'integer',
                                ]
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route8(): mixed
    {
        return new UserResourceCollection([new User]);
    }


    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => null,
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'created_at' => [
                                        'format' => 'date-time',
                                        'type' => 'string',
                                    ],
                                    'email' => [
                                        'type' => 'string',
                                    ],
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'updated_at' => [
                                        'format' => 'date-time',
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route9(): mixed
    {
        return new UserCollection([new User]);
    }


    /**
     * Route 10
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 10',
        'description' => '',
        'parameters' => [],
        'requestBody' => null,
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'collection' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'format' => 'date-time',
                                                'type' => 'string',
                                            ],
                                            'email' => [
                                                'type' => 'string',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'updated_at' => [
                                                'format' => 'date-time',
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'count' => [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route10(): mixed
    {
        $collection = new UserCollection([new User]);
        
        return [
            'collection' => $collection,
            'count' => $collection->count(),
        ];
    }
}

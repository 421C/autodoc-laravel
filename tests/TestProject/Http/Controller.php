<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\StateEnum;
use AutoDoc\Laravel\Tests\TestProject\Entities\User;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserCollection;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResource;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResourceCollection;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;

/**
 * @phpstan-type Symbol 'a'|'b'|'c'
 */
class Controller
{
    #[ExpectedOperationSchema([
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
                                'description' => 'Required if "use_client_id" is true.',
                                'format' => 'uuid',
                            ],
                            'use_client_id' => [
                                'type' => 'boolean',
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
    ])]
    public function route3(Request $request): void
    {
        $validationRules = [
            'use_client_id' => 'boolean',
            'client_id' => 'integer',
            'symbols' => [new ArrayRule],
        ];

        $validationRules['client_id'] = 'required_if_accepted:use_client_id' . '|nullable|uuid';

        $validationRules['status'][] = new Enum(StateEnum::class);

        $validationRules['position.x'] = [
            new In([1, 2, 3, '*']),
        ];

        $request->validate($validationRules);
    }


    #[ExpectedOperationSchema([
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
                                ],
                            ],
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
                                ],
                            ],
                            'required' => [
                                'status',
                                'n',
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
        'responses' => [
            200 => [
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
                                'email' => [
                                    'type' => 'string',
                                ],
                                'created_at' => [
                                    'format' => 'date-time',
                                    'type' => 'string',
                                ],
                                'updated_at' => [
                                    'format' => 'date-time',
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'status' => [
                                    'description' => '[StateEnum](#/schemas/StateEnum)',
                                    'enum' => [
                                        1,
                                        2,
                                    ],
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'email',
                                'created_at',
                                'updated_at',
                                'status',
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
                                        'required' => [
                                            'id',
                                            'name',
                                            'email',
                                            'created_at',
                                            'updated_at',
                                            'status',
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
                                        'required' => [
                                            'id',
                                            'name',
                                            'email',
                                            'created_at',
                                            'updated_at',
                                            'status',
                                        ],
                                    ],
                                ],
                                'count' => [
                                    'description' => 'Number of users returned.',
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'users',
                                'count',
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
                                'required' => [
                                    'id',
                                    'name',
                                    'email',
                                    'created_at',
                                    'updated_at',
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
                                        'required' => [
                                            'id',
                                            'name',
                                            'email',
                                            'created_at',
                                            'updated_at',
                                        ],
                                    ],
                                ],
                                'count' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'collection',
                                'count',
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


    /**
     * Route 11
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 11',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'how many' => [
                                    'type' => 'string',
                                    'format' => 'numeric',
                                ],
                                'token' => [
                                    'type' => 'string',
                                    'minLength' => 12,
                                ],
                                'token_confirmation' => [
                                    'type' => 'string',
                                    'description' => 'Must match "token".',
                                ],
                                'what' => [
                                    'type' => 'array',
                                    'items' => [
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
    public function route11(): void
    {
        request()->validate([
            '*.what' => 'array|boolean',
            '*.how many' => 'string|numeric',
            '*.token' => 'confirmed|min:12',
        ]);
    }


    /**
     * Route 12
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 12',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'token' => [
                                'type' => 'string',
                                'format' => 'password',
                            ],
                            'token_confirmation' => [
                                'type' => 'string',
                                'description' => 'Must match "token".',
                                'format' => 'password',
                            ],
                        ],
                        'required' => [
                            'token',
                            'token_confirmation',
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
    ])]
    public function route12(): void
    {
        request()->validate([
            'token' => ['required', Password::defaults(), 'confirmed'],
        ]);
    }


    /**
     * Route 13
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 13',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'ip' => [
                                'type' => 'string',
                                'format' => 'ipv6',
                            ],
                            'website' => [
                                'type' => 'object',
                                'properties' => [
                                    'url' => [
                                        'type' => 'string',
                                        'format' => 'uri',
                                    ],
                                ],
                                'required' => [
                                    'url',
                                ],
                            ],
                        ],
                        'required' => [
                            'website',
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'ip' => [
                                            'type' => 'string',
                                            'format' => 'ipv6',
                                        ],
                                        'website' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'url' => [
                                                    'type' => 'string',
                                                    'format' => 'uri',
                                                ],
                                            ],
                                            'required' => [
                                                'url',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'website',
                                    ],
                                ],
                            ],
                            'required' => [
                                'data',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route13(): JsonResponse
    {
        $validated = request()->validate([
            'website.url' => 'url|required',
            'ip' => 'ipv6',
        ]);

        return response()->json([
            'data' => $validated,
        ]);
    }


    /**
     * Route 14
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 14',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'numbers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route14(): JsonResponse
    {
        $validated = request()->validate([
            'numbers' => [
                'array',
                Rule::requiredIf(true),
            ],
            'numbers.*' => [
                'integer',
                Rule::when(true, ['gt:0']),
                Rule::when(true, ['gte:0']),
            ],
        ]);

        return response()->json($validated['numbers']);
    }


    /**
     * Route 15
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 15',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'int' => [
                                'type' => 'integer',
                            ],
                            'int_string' => [
                                'type' => 'string',
                                'format' => 'integer',
                            ],
                            'numeric' => [
                                'type' => 'number',
                            ],
                            'numeric_integer' => [
                                'type' => 'integer',
                            ],
                            'numeric_string' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                                'format' => 'numeric',
                            ],
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'int_string' => [
                                    'type' => 'string',
                                    'format' => 'integer',
                                ],
                                'numeric_string' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'numeric',
                                ],
                            ],
                            'required' => [
                                'numeric_string',
                                'int_string',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function route15(): JsonResponse
    {
        $validated = request()->validate([
            'numeric' => 'numeric',
            'numeric_string' => 'string|numeric|nullable',
            'int' => 'integer',
            'int_string' => 'integer|string',
            'numeric_integer' => 'numeric|integer',
        ]);

        return response()->json([
            'numeric_string' => $validated['numeric_string'],
            'int_string' => $validated['int_string'],
        ]);
    }


    /**
     * Route 16
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 16',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'codes' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                                'required' => [
                                    'codes',
                                ],
                            ],
                        ],
                        'required' => [
                            'data',
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
    ])]
    public function route16(): void
    {
        request()->validate([
            'data.codes' => 'required',
            'data.codes.*' => 'string',
        ]);
    }


    /**
     * Route 17
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 17',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'user' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'email' => [
                                        'type' => 'string',
                                    ],
                                    'created_at' => [
                                        'type' => 'string',
                                        'format' => 'date-time',
                                    ],
                                    'updated_at' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                        'format' => 'date-time',
                                    ],
                                    'status' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'required' => [
                                    'id',
                                    'name',
                                    'email',
                                    'created_at',
                                    'updated_at',
                                    'status',
                                ],
                            ],
                        ],
                        'required' => [
                            'user',
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
    ])]
    public function route17(): void
    {
        request()->validate([
            /**
             * @var User&object{status: string}
             */
            'user' => 'present',
        ]);
    }


    /**
     * Route 18
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 18',
        'description' => '',
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'rocket',
                'required' => true,
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
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'name' => [
                                    'type' => 'string',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function route18(Rocket $rocket): mixed
    {
        return $rocket;
    }


    /**
     * Route 19
     *
     * Implicit enum binding
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 19',
        'description' => 'Implicit enum binding',
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'state',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                    'description' => '[StateEnum](#/schemas/StateEnum)',
                    'enum' => [
                        1,
                        2,
                    ],
                ],
            ],
        ],
    ])]
    public function route19(StateEnum $state): void {}


    /**
     * Route 20
     *
     * @param int $rocketId Description of the identifier.
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 20',
        'description' => '',
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'rocketId',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                    'description' => 'Description of the identifier.',
                ],
            ],
        ],
    ])]
    public function route20(Request $request, int $rocketId): void {}


    /**
     * Route 21
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 21',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'current_page' => [
                                    'type' => 'integer',
                                ],
                                'data' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                        ],
                                    ],
                                ],
                                'first_page_url' => [
                                    'type' => 'string',
                                ],
                                'from' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                                'last_page' => [
                                    'type' => 'integer',
                                ],
                                'last_page_url' => [
                                    'type' => 'string',
                                ],
                                'links' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'active' => [
                                                'type' => 'boolean',
                                            ],
                                            'label' => [
                                                'type' => 'string',
                                            ],
                                            'url' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'next_page_url' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'path' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'per_page' => [
                                    'type' => 'integer',
                                ],
                                'prev_page_url' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'to' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                                'total' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'current_page',
                                'first_page_url',
                                'from',
                                'last_page',
                                'last_page_url',
                                'next_page_url',
                                'path',
                                'per_page',
                                'prev_page_url',
                                'to',
                                'total',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function route21(): JsonResponse
    {
        $rockets = Rocket::paginate(100);

        return response()->json($rockets);
    }


    /**
     * Route 22
     *
     * @request-header planet-id {description: 'Planet ID'}
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 22',
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
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function route22(): mixed
    {
        $planet = new Planet;

        $planet->id = 1;

        return $planet;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\StateEnum;
use AutoDoc\Laravel\Tests\TestProject\Entities\User;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserCollection;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResource;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResourceCollection;
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
        'responses' => [],
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
                                ],
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


    /**
     * Route 11
     */
    #[ExpectedOperationSchema([
        'summary' => 'Route 11',
        'description' => '',
        'parameters' => [],
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
        'responses' => [],
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
        'parameters' => [],
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
        'responses' => [],
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
        'parameters' => [],
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
        'parameters' => [],
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
        'parameters' => [],
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
}

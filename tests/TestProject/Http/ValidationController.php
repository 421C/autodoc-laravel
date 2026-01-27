<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\StateEnum;
use AutoDoc\Laravel\Tests\TestProject\Entities\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;

/**
 * Tests for Laravel validation rules parsing.
 *
 * @phpstan-type Symbol 'a'|'b'|'c'
 */
class ValidationController
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
    public function basicStringRules(Request $request): void
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
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                [
                                    'type' => 'string',
                                    'format' => 'date-time',
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
    public function nestedArrayRules(): mixed
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
    public function ruleObjects(Request $request): void
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
                                        -0.1,
                                        115,
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
    public function enumRules(FormRequest $request): mixed
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


    /**
     * Wildcard array validation
     */
    #[ExpectedOperationSchema([
        'summary' => 'Wildcard array validation',
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
    public function wildcardArrayValidation(): void
    {
        request()->validate([
            '*.what' => 'array|boolean',
            '*.how many' => 'string|numeric',
            '*.token' => 'confirmed|min:12',
        ]);
    }


    /**
     * Password rule validation
     */
    #[ExpectedOperationSchema([
        'summary' => 'Password rule validation',
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
    public function passwordRule(): void
    {
        request()->validate([
            'token' => ['required', 'confirmed', Password::defaults()],
        ]);
    }


    /**
     * URL and IP validation
     */
    #[ExpectedOperationSchema([
        'summary' => 'URL and IP validation',
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
    public function urlAndIpValidation(): JsonResponse
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
     * Rule::when and Rule::requiredIf
     */
    #[ExpectedOperationSchema([
        'summary' => 'Rule::when and Rule::requiredIf',
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
    public function conditionalRules(): JsonResponse
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
     * Numeric and integer string validation
     */
    #[ExpectedOperationSchema([
        'summary' => 'Numeric and integer string validation',
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
    public function numericValidation(): JsonResponse
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
     * Nested required with wildcard
     */
    #[ExpectedOperationSchema([
        'summary' => 'Nested required with wildcard',
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
    public function nestedRequiredWithWildcard(): void
    {
        request()->validate([
            'data.codes' => 'required',
            'data.codes.*' => 'string',
        ]);
    }


    /**
     * PHPDoc type in validation
     */
    #[ExpectedOperationSchema([
        'summary' => 'PHPDoc type in validation',
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
    public function phpdocTypeInValidation(): void
    {
        request()->validate([
            /**
             * @var User&object{status: string}
             */
            'user' => 'present',
        ]);
    }


    /**
     * Enum with nested and filled rules
     */
    #[ExpectedOperationSchema([
        'summary' => 'Enum with nested and filled rules',
        'description' => '',
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'nested' => [
                                'type' => 'object',
                                'properties' => [
                                    'enum' => [
                                        'type' => [
                                            'integer',
                                            'null',
                                        ],
                                        'description' => '[StateEnum](#/schemas/StateEnum)

status description',
                                        'enum' => [
                                            1,
                                            2,
                                        ],
                                    ],
                                    'filled' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'required' => [
                                    'enum',
                                    'filled',
                                ],
                            ],
                        ],
                        'required' => [
                            'nested',
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
                    ],
                ],
            ],
        ],
    ])]
    public function enumWithNestedAndFilledRules(Request $request): mixed
    {
        $validated = $request->validate([
            /**
             * status description
             */
            'nested.enum' => ['nullable', 'present', new Enum(StateEnum::class)],

            'nested.filled' => ['required', 'filled'],
        ]);

        return $validated['nested']['enum'];
    }
}

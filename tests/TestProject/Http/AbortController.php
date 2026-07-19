<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;

class AbortController
{
    #[ExpectedOperationSchema([
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
            404 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                ],
                            ],
                            'required' => [
                                'message',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function abortWhenModelIsMissing(): mixed
    {
        $planet = Planet::query()->first();

        if (! $planet) {
            abort(404);
        }

        return $planet;
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => [
                                    'type' => 'string',
                                    'const' => 'ok',
                                ],
                            ],
                            'required' => [
                                'status',
                            ],
                        ],
                    ],
                ],
            ],
            403 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                    'const' => 'This action is forbidden',
                                ],
                            ],
                            'required' => [
                                'message',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function abortIfWithMessage(): mixed
    {
        abort_if(rand(0, 1) === 1, 403, 'This action is forbidden');

        return response()->json(['status' => 'ok']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => [
                                    'type' => 'string',
                                    'const' => 'ok',
                                ],
                            ],
                            'required' => [
                                'status',
                            ],
                        ],
                    ],
                ],
            ],
            401 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                    'const' => 'Unauthenticated',
                                ],
                            ],
                            'required' => [
                                'message',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function abortUnlessWithNamedArguments(): mixed
    {
        abort_unless(rand(0, 1) === 1, code: 401, message: 'Unauthenticated');

        return response()->json(['status' => 'ok']);
    }
}

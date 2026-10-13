<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;

/**
 * Tests for Laravel FormRequest validation.
 */
class FormRequestController
{
    #[ExpectedOperationSchema([
        'requestBody' => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'access_token' => [
                                'type' => 'string',
                                'format' => 'uuid',
                            ],
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
                            'access_token',
                        ],
                    ],
                ],
            ],
            'description' => '',
            'required' => false,
        ],
        'responses' => [
            422 => [
                'description' => '',
            ],
        ],
    ])]
    public function itemsRequestWithExtraParameters(ItemsRequest $request, string $id): void
    {
        $request->validate([
            'access_token' => 'uuid|required',
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
            422 => [
                'description' => '',
            ],
        ],
    ])]
    public function itemsRequest(ItemsRequest $request): mixed
    {
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
        return response()->json($request->items[0]['id'] ?? null);
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
                            'names' => [
                                'type' => ['array', 'null'],
                                'items' => [
                                    'type' => 'string',
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
            422 => [
                'description' => '',
            ],
        ],
    ])]
    public function extendedItemsRequest(ExtendedItemsRequest $request): void {}


    #[ExpectedOperationSchema([
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'password' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                                'format' => 'password',
                            ],
                            'password_confirmation' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                                'format' => 'password',
                                'description' => 'Must match "password".',
                            ],
                        ],
                        'required' => [
                            'password',
                            'password_confirmation',
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
        'responses' => [
            422 => [
                'description' => '',
            ],
        ],
    ])]
    public function passwordConfirmation(PasswordRequest $request): void {}
}

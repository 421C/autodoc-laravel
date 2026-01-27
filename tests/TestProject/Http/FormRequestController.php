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
    public function customFormRequest(CustomRequest $request): mixed
    {
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
        return response()->json($request->items[0]['id'] ?? null);
    }
}

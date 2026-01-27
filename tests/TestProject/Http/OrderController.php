<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\OrderStatus;
use AutoDoc\Laravel\Tests\TestProject\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class OrderController
{
    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'order',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                ],
            ],
        ],
        'requestBody' => [
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'integer',
                                'description' => '[OrderStatus](#/schemas/OrderStatus)',
                                'enum' => [
                                    1,
                                    2,
                                    3,
                                    4,
                                ],
                            ],
                        ],
                        'required' => [
                            'status',
                        ],
                    ],
                ],
            ],
            'required' => false,
        ],
        'responses' => [
            400 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                    'const' => 'Completed orders can’t be changed',
                                ],
                                'order' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'created_at' => [
                                            'type' => 'string',
                                            'format' => 'date-time',
                                        ],
                                        'id' => [
                                            'type' => 'integer',
                                        ],
                                        'status' => [
                                            'type' => 'integer',
                                            'description' => '[OrderStatus](#/schemas/OrderStatus)',
                                            'enum' => [
                                                1,
                                                2,
                                                3,
                                                4,
                                            ],
                                        ],
                                        'updated_at' => [
                                            'type' => 'string',
                                            'format' => 'date-time',
                                        ],
                                    ],
                                    'required' => [
                                        'id',
                                        'status',
                                    ],
                                ],
                                'status_updated' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'status_updated',
                                'message',
                                'order',
                            ],
                        ],
                    ],
                ],
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'products' => [
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
                                'status_updated' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'status_updated',
                                'products',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', new Enum(OrderStatus::class)],
        ]);

        if ($order->status === OrderStatus::Completed) { // @phpstan-ignore property.notFound
            return response()->json([
                'status_updated' => false,
                'message' => 'Completed orders can’t be changed',
                'order' => array_filter($order->toArray()),
            ], 400);
        }

        $order->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'status_updated' => true,
            'products' => $order->products->map(fn ($product) => [ // @phpstan-ignore argument.unresolvableType, method.unresolvableReturnType
                'id' => $product->id,
                'name' => $product->name, // @phpstan-ignore property.notFound
            ]),
        ]);
    }
}

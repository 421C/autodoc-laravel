<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Tests for raw (non-Eloquent) DB query builder chains.
 */
class RawQueryController
{
    /**
     * Raw DB query with collection map and sum
     */
    #[ExpectedOperationSchema([
        'summary' => 'Raw DB query with collection map and sum',
        'responses' => [
            200 => [
                'description' => '',
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
                                            'institution_code' => [
                                                'type' => 'string',
                                            ],
                                            'branch_code' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                            'referral_count' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                        'required' => [
                                            'institution_code',
                                            'branch_code',
                                            'referral_count',
                                        ],
                                    ],
                                ],
                                'total' => [
                                    'type' => 'number',
                                ],
                            ],
                            'required' => [
                                'data',
                                'total',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function rawQueryWithMapAndSum(): JsonResponse
    {
        $union = DB::table('planets')->select(['id', 'name']);

        $rows = DB::query()
            ->fromSub($union, 'referrals')
            ->when(true, fn ($query) => $query->where('referral_count', '>', 0))
            ->select(['institution_code', 'branch_code', 'referral_count'])
            ->groupBy('institution_code', 'branch_code')
            ->orderByDesc('referral_count')
            ->get()
            ->map(fn (object $row) => (array) $row);

        $data = $rows->map(function (array $row) {
            return [
                'institution_code' => (string) $row['institution_code'],
                'branch_code' => isset($row['branch_code']) ? (string) $row['branch_code'] : null,
                'referral_count' => (int) $row['referral_count'],
            ];
        });

        $total = $data->sum('referral_count');

        return response()->json([
            'data' => $data,
            'total' => $total,
        ]);
    }


    /**
     * Collection sum
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection sum',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'priceTotal' => [
                                    'type' => 'number',
                                ],
                                'weightTotal' => [
                                    'type' => 'number',
                                ],
                                'plainTotal' => [
                                    'type' => 'number',
                                ],
                            ],
                            'required' => [
                                'priceTotal',
                                'weightTotal',
                                'plainTotal',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function collectionSum(): JsonResponse
    {
        $items = collect([
            [
                'name' => 'a',
                'price' => 10,
                'weight' => 1.5,
            ],
            [
                'name' => 'b',
                'price' => 20,
                'weight' => 2.25,
            ],
        ]);

        return response()->json([
            'priceTotal' => $items->sum('price'),
            'weightTotal' => $items->sum('weight'),
            'plainTotal' => collect([1, 2, 3])->sum(),
        ]);
    }


    /**
     * Raw DB query scalar finishers
     */
    #[ExpectedOperationSchema([
        'summary' => 'Raw DB query scalar finishers',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'count' => [
                                    'type' => 'integer',
                                    'minimum' => 0,
                                ],
                                'exists' => [
                                    'type' => 'boolean',
                                ],
                                'doesntExist' => [
                                    'type' => 'boolean',
                                ],
                                'sum' => [
                                    'type' => 'number',
                                ],
                                'avg' => [
                                    'type' => [
                                        'number',
                                        'null',
                                    ],
                                ],
                            ],
                            'required' => [
                                'count',
                                'exists',
                                'doesntExist',
                                'sum',
                                'avg',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function rawQueryScalarFinishers(): JsonResponse
    {
        return response()->json([
            'count' => DB::table('planets')->count(),
            'exists' => DB::table('planets')->where('visited', true)->exists(),
            'doesntExist' => DB::table('planets')->where('visited', true)->doesntExist(),
            'sum' => DB::table('planets')->sum('diameter'),
            'avg' => DB::table('planets')->avg('diameter'),
        ]);
    }


    /**
     * DB transaction callback return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'DB transaction callback return value',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'committed' => [
                                    'type' => 'boolean',
                                ],
                                'transactionId' => [
                                    'type' => 'integer',
                                    'const' => 123,
                                ],
                            ],
                            'required' => [
                                'committed',
                                'transactionId',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function transaction(): JsonResponse
    {
        $result = DB::transaction(function () {
            return [
                'committed' => true,
                'transactionId' => 123,
            ];
        });

        return response()->json($result);
    }
}

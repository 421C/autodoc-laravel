<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'brief',
                'schema' => [
                    'type' => 'boolean',
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
                                'aliasedTable' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                        ],
                                        'required' => [
                                            'diameter',
                                        ],
                                    ],
                                ],
                                'conditional' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'created_at' => [
                                                        'type' => [
                                                            'string',
                                                            'null',
                                                        ],
                                                        'format' => 'date-time',
                                                    ],
                                                    'diameter' => [
                                                        'type' => 'number',
                                                        'format' => 'float',
                                                    ],
                                                    'id' => [
                                                        'type' => 'integer',
                                                    ],
                                                    'name' => [
                                                        'type' => 'string',
                                                    ],
                                                    'updated_at' => [
                                                        'type' => [
                                                            'string',
                                                            'null',
                                                        ],
                                                        'format' => 'date-time',
                                                    ],
                                                    'visited' => [
                                                        'type' => 'integer',
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
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => [
                                                        'type' => 'integer',
                                                    ],
                                                ],
                                                'required' => [
                                                    'id',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'first' => [
                                    'type' => [
                                        'object',
                                        'null',
                                    ],
                                    'properties' => [
                                        'created_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'diameter' => [
                                            'type' => 'number',
                                            'format' => 'float',
                                        ],
                                        'id' => [
                                            'type' => 'integer',
                                        ],
                                        'name' => [
                                            'type' => 'string',
                                        ],
                                        'updated_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'visited' => [
                                            'type' => 'integer',
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
                                'fromSubquery' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                    ],
                                ],
                                'joined' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date-time',
                                            ],
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'launch_date' => [
                                                'type' => 'string',
                                                'format' => 'date',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'target_planet_id' => [
                                                'type' => 'integer',
                                            ],
                                            'updated_at' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date-time',
                                            ],
                                            'visited' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                            'diameter',
                                            'visited',
                                            'created_at',
                                            'updated_at',
                                            'launch_date',
                                            'target_planet_id',
                                        ],
                                    ],
                                ],
                                'leftJoined' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date-time',
                                            ],
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                            'id' => [
                                                'type' => [
                                                    'integer',
                                                    'null',
                                                ],
                                            ],
                                            'launch_date' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date',
                                            ],
                                            'name' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                            'target_planet_id' => [
                                                'type' => [
                                                    'integer',
                                                    'null',
                                                ],
                                            ],
                                            'updated_at' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date-time',
                                            ],
                                            'visited' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                            'diameter',
                                            'visited',
                                            'created_at',
                                            'updated_at',
                                            'launch_date',
                                            'target_planet_id',
                                        ],
                                    ],
                                ],
                                'joinedWithPrefixedSelect' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'launch_date' => [
                                                'type' => 'string',
                                                'format' => 'date',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'rocket_id' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                        'required' => [
                                            'name',
                                            'launch_date',
                                            'rocket_id',
                                        ],
                                    ],
                                ],
                                'joinedSubquery' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                    ],
                                ],
                                'keyedPluck' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
                                        'type' => 'number',
                                        'format' => 'float',
                                    ],
                                ],
                                'namedConnection' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'required' => [
                                            'name',
                                        ],
                                    ],
                                ],
                                'paginated' => [
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
                                                    'created_at' => [
                                                        'type' => [
                                                            'string',
                                                            'null',
                                                        ],
                                                        'format' => 'date-time',
                                                    ],
                                                    'diameter' => [
                                                        'type' => 'number',
                                                        'format' => 'float',
                                                    ],
                                                    'id' => [
                                                        'type' => 'integer',
                                                    ],
                                                    'name' => [
                                                        'type' => 'string',
                                                    ],
                                                    'updated_at' => [
                                                        'type' => [
                                                            'string',
                                                            'null',
                                                        ],
                                                        'format' => 'date-time',
                                                    ],
                                                    'visited' => [
                                                        'type' => 'integer',
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
                                'selected' => [
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
                                'value' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'wholeTable' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date-time',
                                            ],
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'updated_at' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date-time',
                                            ],
                                            'visited' => [
                                                'type' => 'integer',
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
                            'required' => [
                                'wholeTable',
                                'selected',
                                'aliasedTable',
                                'first',
                                'value',
                                'keyedPluck',
                                'paginated',
                                'joined',
                                'leftJoined',
                                'joinedWithPrefixedSelect',
                                'joinedSubquery',
                                'namedConnection',
                                'fromSubquery',
                                'conditional',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function tableRowShapes(Request $request): mixed
    {
        $conditional = DB::table('planets');

        if ($request->boolean('brief')) {
            $conditional->select('id');
        }

        return [
            'wholeTable' => DB::table('planets')->get(),
            'selected' => DB::table('planets')->select('id', 'name')->get(),
            'aliasedTable' => DB::table('planets as p')->select('p.diameter')->get(),
            'first' => DB::table('planets')->where('visited', true)->first(),
            'value' => DB::table('planets')->value('name'),
            'keyedPluck' => DB::table('planets')->pluck('diameter', 'name'),
            'paginated' => DB::table('planets')->paginate(15),
            'joined' => DB::table('planets')->join('rockets', 'rockets.target_planet_id', '=', 'planets.id')->get(),
            'leftJoined' => DB::table('planets')->leftJoin('rockets', 'rockets.target_planet_id', '=', 'planets.id')->get(),
            'joinedWithPrefixedSelect' => DB::table('planets as p')
                ->join('rockets as r', 'r.target_planet_id', '=', 'p.id')
                ->select('p.name', 'r.launch_date', 'r.id as rocket_id')
                ->get(),
            'joinedSubquery' => DB::table('planets')
                ->joinSub(DB::table('rockets'), 'r', 'r.target_planet_id', '=', 'planets.id')
                ->select('planets.name')
                ->get(),
            'namedConnection' => DB::connection('testing')->table('planets')->select('name')->get(),
            'fromSubquery' => DB::query()->fromSub(DB::table('planets'), 'p')->get(),
            'conditional' => $conditional->get(),
        ];
    }
}

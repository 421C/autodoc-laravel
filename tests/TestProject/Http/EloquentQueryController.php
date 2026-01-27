<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
use AutoDoc\Laravel\Tests\TestProject\Models\SpaceStation;
use Illuminate\Http\JsonResponse;

/**
 * Tests for Eloquent queries, collections, select, relationships.
 */
class EloquentQueryController
{
    /**
     * Query builder with select
     */
    #[ExpectedOperationSchema([
        'summary' => 'Query builder with select',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
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
                    ],
                ],
            ],
        ],
    ])]
    public function queryBuilderWithSelect(): mixed
    {
        // Return type read from model's `toArray` method.
        return Rocket::select('id', 'launch_date')
            ->where('id', '!=', 1)
            ->where('launch_date', '>=', '2026-01-01')
            ->get();
    }


    /**
     * Select with specific columns
     */
    #[ExpectedOperationSchema([
        'summary' => 'Select with specific columns',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
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
                                ],
                                'required' => [
                                    'name',
                                    'diameter',
                                    'visited',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function selectWithSpecificColumns(): mixed
    {
        return Planet::select('name', 'diameter', 'visited')
            ->where('diameter', '>=', 1000)
            ->get();
    }


    /**
     * Select with addSelect
     */
    #[ExpectedOperationSchema([
        'summary' => 'Select with addSelect',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
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
                                    'diameter' => [
                                        'type' => 'number',
                                        'format' => 'float',
                                    ],
                                ],
                                'required' => [
                                    'id',
                                    'name',
                                    'diameter',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function selectWithAddSelect(): mixed
    {
        $nameColumn = 'name';

        return Planet::query()
            ->select(['id', $nameColumn])
            ->limit(1)
            ->addSelect('diameter')
            ->get();
    }


    /**
     * Select with alias
     */
    #[ExpectedOperationSchema([
        'summary' => 'Select with alias',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'planet_id' => [
                                        'type' => 'integer',
                                    ],
                                ],
                                'required' => [
                                    'planet_id',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function selectWithAlias(): mixed
    {
        $query = Planet::select(...['planets.id as planet_id']);

        return $query->get();
    }


    /**
     * Pluck single column
     */
    #[ExpectedOperationSchema([
        'summary' => 'Pluck single column',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
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
    ])]
    public function pluckSingleColumn(): mixed
    {
        return Planet::where('updated_at', '>=', '2026-01-01')->pluck('updated_at');
    }


    /**
     * All with column alias
     */
    #[ExpectedOperationSchema([
        'summary' => 'All with column alias',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'planet_name' => [
                                        'type' => 'string',
                                    ],
                                    'diameter' => [
                                        'type' => 'number',
                                        'format' => 'float',
                                    ],
                                ],
                                'required' => [
                                    'planet_name',
                                    'diameter',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function allWithColumnAlias(): mixed
    {
        return Planet::all(['name as planet_name', 'diameter']);
    }


    /**
     * Collection map with arithmetic
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection map with arithmetic',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'number',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function collectionMapWithArithmetic(): mixed
    {
        /** @phpstan-ignore property.notFound */
        return Planet::get()->map(fn ($planet) => $planet->diameter * 100);
    }


    /**
     * Collection map with property access
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection map with property access',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'boolean',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function collectionMapWithPropertyAccess(): mixed
    {
        /** @phpstan-ignore argument.templateType */
        return Planet::all()->map(function ($planet) {
            /** @phpstan-ignore property.notFound */
            $isVisited = $planet->visited;

            return $isVisited;
        });
    }


    /**
     * Chained collection methods
     */
    #[ExpectedOperationSchema([
        'summary' => 'Chained collection methods',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'array',
                                'items' => [
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
    ])]
    public function chainedCollectionMethods(): mixed
    {
        return Rocket::query()
            ->where('launch_date', '>=', '2026-01-01')
            ->pluck('updated_at')
            ->filter(fn ($updateDate) => $updateDate !== '2026-01-01')
            ->map(fn ($updateDate) => [$updateDate])
            ->toArray();
    }


    /**
     * Model with accessors and mutated properties
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model with accessors and mutated properties',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'mutated_id' => [
                                        'anyOf' => [
                                            [
                                                'type' => 'string',
                                                'const' => '',
                                            ],
                                            [
                                                'type' => 'integer',
                                            ],
                                        ],
                                    ],
                                    'name' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],
                                    'description' => [
                                        'type' => 'string',
                                    ],
                                    'coordinates' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'reference' => [
                                                'type' => 'string',
                                                'const' => 'Galactic Center',
                                            ],
                                            'x' => [
                                                'type' => 'number',
                                                'const' => 123000,
                                                'format' => 'float',
                                            ],
                                            'y' => [
                                                'type' => 'number',
                                                'const' => -456000,
                                                'format' => 'float',
                                            ],
                                            'z' => [
                                                'type' => 'number',
                                                'const' => 789000,
                                                'format' => 'float',
                                            ],
                                        ],
                                        'required' => [
                                            'x',
                                            'y',
                                            'z',
                                            'reference',
                                        ],
                                    ],
                                    'size' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'required' => [
                                    'mutated_id',
                                    'name',
                                    'description',
                                    'size',
                                    'coordinates',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelWithAccessorsAndMutatedProperties(): mixed
    {
        /** @phpstan-ignore method.unresolvableReturnType */
        return SpaceStation::query()
            ->get()
            /** @phpstan-ignore argument.unresolvableType */
            ->map(fn ($station) => [
                'mutated_id' => $station->id,
                'name' => $station->name,
                /** @phpstan-ignore property.notFound */
                'description' => $station->description,
                /** @phpstan-ignore property.notFound */
                'size' => $station->size,
                'coordinates' => $station->coordinates,
            ]);
    }


    /**
     * First and firstOrFail with query builder
     */
    #[ExpectedOperationSchema([
        'summary' => 'First and firstOrFail with query builder',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'first' => [
                                    'type' => [
                                        'object',
                                        'null',
                                    ],
                                    'properties' => [
                                        'description' => [
                                            'type' => 'string',
                                        ],
                                        'created_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'id' => [
                                            'anyOf' => [
                                                [
                                                    'type' => 'string',
                                                    'const' => '',
                                                ],
                                                [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                        ],
                                        'name' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                        ],
                                        'size' => [
                                            'type' => 'string',
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
                                        'description',
                                        'size',
                                        'created_at',
                                        'updated_at',
                                    ],
                                ],
                                'firstOrFail' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'description' => [
                                            'type' => 'string',
                                        ],
                                        'created_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'id' => [
                                            'anyOf' => [
                                                [
                                                    'type' => 'string',
                                                    'const' => '',
                                                ],
                                                [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                        ],
                                        'name' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                        ],
                                        'size' => [
                                            'type' => 'string',
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
                                        'description',
                                        'size',
                                        'created_at',
                                        'updated_at',
                                    ],
                                ],
                            ],
                            'required' => [
                                'first',
                                'firstOrFail',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function firstAndFirstOrFailWithQueryBuilder(): mixed
    {
        return [
            'first' => SpaceStation::query()->first(),
            'firstOrFail' => SpaceStation::query()->firstOrFail(),
        ];
    }


    /**
     * First and firstOrFail static
     */
    #[ExpectedOperationSchema([
        'summary' => 'First and firstOrFail static',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'first' => [
                                    'type' => [
                                        'object',
                                        'null',
                                    ],
                                    'properties' => [
                                        'description' => [
                                            'type' => 'string',
                                        ],
                                        'created_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'id' => [
                                            'anyOf' => [
                                                [
                                                    'type' => 'string',
                                                    'const' => '',
                                                ],
                                                [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                        ],
                                        'name' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                        ],
                                        'size' => [
                                            'type' => 'string',
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
                                        'description',
                                        'size',
                                        'created_at',
                                        'updated_at',
                                    ],
                                ],
                                'firstOrFail' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'description' => [
                                            'type' => 'string',
                                        ],
                                        'created_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'id' => [
                                            'anyOf' => [
                                                [
                                                    'type' => 'string',
                                                    'const' => '',
                                                ],
                                                [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                        ],
                                        'name' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                        ],
                                        'size' => [
                                            'type' => 'string',
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
                                        'description',
                                        'size',
                                        'created_at',
                                        'updated_at',
                                    ],
                                ],
                            ],
                            'required' => [
                                'first',
                                'firstOrFail',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function firstAndFirstOrFailStatic(): mixed
    {
        return [
            'first' => SpaceStation::first(),
            'firstOrFail' => SpaceStation::firstOrFail(),
        ];
    }


    /**
     * Collection methods on literal array
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection methods on literal array',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'first' => [
                                    'type' => [
                                        'object',
                                        'null',
                                    ],
                                    'properties' => [
                                        'id' => [
                                            'type' => 'integer',
                                            'enum' => [
                                                1,
                                                2,
                                            ],
                                        ],
                                        'name' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'jānis',
                                                'bānis',
                                            ],
                                        ],
                                    ],
                                ],
                                'lastWithDefaultFalse' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => [
                                                    'type' => 'integer',
                                                    'enum' => [
                                                        1,
                                                        2,
                                                    ],
                                                ],
                                                'name' => [
                                                    'type' => 'string',
                                                    'enum' => [
                                                        'jānis',
                                                        'bānis',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'type' => 'boolean',
                                        ],
                                    ],
                                ],
                                'pluckId' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'integer',
                                        'enum' => [
                                            1,
                                            2,
                                        ],
                                    ],
                                ],
                                'mapId' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'integer',
                                        'enum' => [
                                            1,
                                            2,
                                        ],
                                    ],
                                ],
                                'mapWithKeys' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'object',
                                            'properties' => [
                                                'jānis' => [
                                                    'type' => 'integer',
                                                    'enum' => [
                                                        1,
                                                        2,
                                                    ],
                                                ],
                                            ],
                                            'required' => [
                                                'jānis',
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'properties' => [
                                                'bānis' => [
                                                    'type' => 'integer',
                                                    'enum' => [
                                                        1,
                                                        2,
                                                    ],
                                                ],
                                            ],
                                            'required' => [
                                                'bānis',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'required' => [
                                'first',
                                'lastWithDefaultFalse',
                                'pluckId',
                                'mapId',
                                'mapWithKeys',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function collectionMethodsOnLiteralArray(): mixed
    {
        $array = [
            [
                'id' => 1,
                'name' => 'jānis',
            ],
            [
                'id' => 2,
                'name' => 'bānis',
            ],
        ];

        return [
            'first' => collect($array)->first(),
            'lastWithDefaultFalse' => collect($array)->last(null, false),
            'pluckId' => collect($array)->pluck('id'),
            'mapId' => collect($array)->map(fn ($item) => $item['id']),
            'mapWithKeys' => collect($array)->mapWithKeys(fn ($item) => [$item['name'] => $item['id']]),
        ];
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'anyOf' => [
                                    [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => [
                                                'string',
                                                'integer',
                                                'null',
                                            ],
                                        ],
                                    ],
                                    [
                                        'type' => 'integer',
                                        'minimum' => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function countAndPluck(): mixed
    {
        $count = SpaceStation::count();

        if ($count > 1000) {
            $column = 'id';

        } else {
            $column = 'created_at';
        }

        return [SpaceStation::pluck($column), $count];
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'properties' => [
                                'name' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'size' => [
                                    'type' => 'string',
                                ],
                            ],
                            'required' => [
                                'name',
                                'size',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function selectWithFirst(): ?SpaceStation
    {
        return SpaceStation::select('name', 'size')->first();
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'entry' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'date' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                                'format' => 'date-time',
                                            ],
                                            'name' => [
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                        'required' => [
                                            'name',
                                            'date',
                                        ],
                                    ],
                                ],
                                'required' => [
                                    'entry',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function filterAndMapCollection(): mixed
    {
        /** @phpstan-ignore method.unresolvableReturnType */
        return SpaceStation::where('created_at', '>', now()->subYear())
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn ($station) => $station->name !== null)
            /** @phpstan-ignore argument.unresolvableType */
            ->map(fn ($station) => [
                'entry' => [
                    'name' => $station->name,
                    /** @phpstan-ignore property.notFound */
                    'date' => $station->created_at,
                ],
            ]);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => [
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
    ])]
    public function pluckWithKey(): mixed
    {
        return SpaceStation::query()
            ->get()
            ->pluck('name', 'created_at');
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => [
                                'object',
                                'null',
                            ],
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
                ],
            ],
        ],
    ])]
    public function collectionGetMethod(): mixed
    {
        return Planet::all(['name'])->get(1);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
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
            ],
        ],
    ])]
    public function insertGetIdAndCount(): mixed
    {
        $id = SpaceStation::insertGetId([
            'name' => 'x',
            'size' => 'big',
        ]);

        $result = [];

        $result[] = $id;
        $result[] = SpaceStation::query()->count();

        return $result;
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'anyOf' => [
                                            [
                                                'type' => 'string',
                                                'const' => '',
                                            ],
                                            [
                                                'type' => 'integer',
                                            ],
                                        ],
                                    ],
                                    'name' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
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
        ],
    ])]
    public function sortByAndValues(): mixed
    {
        return SpaceStation::query()
            ->select('space_stations.id', 'space_stations.name')
            ->get()
            ->sortBy('created_at')
            ->values()
            ->toArray();
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'description' => [
                                        'type' => 'string',
                                    ],
                                    'created_at' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                        'format' => 'date-time',
                                    ],
                                    'id' => [
                                        'anyOf' => [
                                            [
                                                'type' => 'string',
                                                'const' => '',
                                            ],
                                            [
                                                'type' => 'integer',
                                            ],
                                        ],
                                    ],
                                    'name' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],
                                    'priority' => [
                                        'type' => [
                                            'integer',
                                            'boolean',
                                        ],
                                    ],
                                    'size' => [
                                        'type' => 'string',
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
                                    'priority',
                                    'id',
                                    'name',
                                    'description',
                                    'size',
                                    'created_at',
                                    'updated_at',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function mapWithSpreadOperator(): mixed
    {
        return SpaceStation::where([])
            ->get()
            ->map(fn ($station) => [
                /** @phpstan-ignore property.notFound */
                'priority' => array_search($station->size, ['big', 'small']),
                ...$station->toArray(),
            ]);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
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
                                    'type' => 'boolean',
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
        ],
    ])]
    public function relationshipAccess(): mixed
    {
        $planet = Rocket::firstWhere('name', '123');

        /** @phpstan-ignore property.nonObject */
        return $planet->targetPlanet;
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'diameter' => [
                                        'type' => 'number',
                                        'format' => 'float',
                                    ],
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                    'space_stations' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => [
                                                    'anyOf' => [
                                                        [
                                                            'type' => 'string',
                                                            'const' => '',
                                                        ],
                                                        [
                                                            'type' => 'integer',
                                                        ],
                                                    ],
                                                ],
                                                'name' => [
                                                    'type' => [
                                                        'string',
                                                        'null',
                                                    ],
                                                ],
                                            ],
                                            'required' => [
                                                'id',
                                                'name',
                                            ],
                                        ],
                                    ],
                                ],
                                'required' => [
                                    'id',
                                    'diameter',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function eagerLoadingWithSelect(): JsonResponse
    {
        return response()->json(Planet::with('spaceStations:id,name')->select('id', 'diameter')->get());
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
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
                                    'space_stations' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'description' => [
                                                    'type' => 'string',
                                                ],
                                                'name' => [
                                                    'type' => [
                                                        'string',
                                                        'null',
                                                    ],
                                                ],
                                                'rockets' => [
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
                                            ],
                                            'required' => [
                                                'name',
                                                'description',
                                            ],
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
                                        'type' => 'boolean',
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
            ],
        ],
    ])]
    public function nestedEagerLoading(): JsonResponse
    {
        return response()->json(
            Planet::query()
                ->with([
                    'spaceStations:name,description' => fn ($query) => $query->where('id', '>', 1),
                    'spaceStations.rockets',
                ])
                ->get()
                ->keyBy('id')
        );
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'properties' => [
                                'id' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'string',
                                            'const' => '',
                                        ],
                                        [
                                            'type' => 'integer',
                                        ],
                                    ],
                                ],
                                'planet' => [
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
                                        'rockets' => [
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
                                        'updated_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'visited' => [
                                            'type' => 'boolean',
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
                            'required' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function nestedRelationshipLoading(): JsonResponse
    {
        return response()->json(
            SpaceStation::select(['id'])
                ->with('planet.rockets')
                ->get()
                ->get(0)
        );
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
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
                                'planet' => [
                                    'type' => [
                                        'object',
                                        'null',
                                    ],
                                    'properties' => [
                                        'diameter' => [
                                            'type' => 'number',
                                            'format' => 'float',
                                        ],
                                        'id' => [
                                            'type' => 'integer',
                                        ],
                                        'rockets' => [
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
                                    ],
                                    'required' => [
                                        'id',
                                        'diameter',
                                    ],
                                ],
                            ],
                            'required' => [
                                'created_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function withArraySyntax(): ?SpaceStation
    {
        return SpaceStation::where([])
            ->limit(1)
            ->with([
                'planet:id,diameter' => [
                    'rockets' => static function () {},
                ],
            ])
            ->select('created_at')
            ->first();
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
                                'description' => [
                                    'type' => 'string',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'id' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'string',
                                            'const' => '',
                                        ],
                                        [
                                            'type' => 'integer',
                                        ],
                                    ],
                                ],
                                'name' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'planet' => [
                                    'type' => [
                                        'object',
                                        'null',
                                    ],
                                    'properties' => [
                                        'diameter' => [
                                            'type' => 'number',
                                            'format' => 'float',
                                        ],
                                        'id' => [
                                            'type' => 'integer',
                                        ],
                                        'space_stations' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'description' => [
                                                        'type' => 'string',
                                                    ],
                                                    'created_at' => [
                                                        'type' => [
                                                            'string',
                                                            'null',
                                                        ],
                                                        'format' => 'date-time',
                                                    ],
                                                    'id' => [
                                                        'anyOf' => [
                                                            [
                                                                'type' => 'string',
                                                                'const' => '',
                                                            ],
                                                            [
                                                                'type' => 'integer',
                                                            ],
                                                        ],
                                                    ],
                                                    'name' => [
                                                        'type' => [
                                                            'string',
                                                            'null',
                                                        ],
                                                    ],
                                                    'rockets' => [
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
                                                    'size' => [
                                                        'type' => 'string',
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
                                                    'description',
                                                    'size',
                                                    'created_at',
                                                    'updated_at',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'id',
                                        'diameter',
                                    ],
                                ],
                                'size' => [
                                    'type' => 'string',
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
                                'description',
                                'size',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function complexNestedRelations(): SpaceStation
    {
        $relations = [
            'planet:id,diameter',
            'planet' => ['spaceStations.rockets:id'],
        ];

        return SpaceStation::with($relations)->firstOrFail();
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'anyOf' => [
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'updated_at' => [
                                            'type' => 'string',
                                            'description' => 'Updated at (UTC)',
                                            'format' => 'date-time',
                                        ],
                                        'visited' => [
                                            'type' => 'boolean',
                                            'description' => 'Is the planet visited?',
                                        ],
                                    ],
                                    'required' => [
                                        'visited',
                                        'updated_at',
                                    ],
                                ],
                                [
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
        ],
    ])]
    public function conditionalReturnWithRelation(): mixed
    {
        $station = SpaceStation::first();

        if ($station?->planet) {
            return [
                /**
                 * Is the planet visited?
                 *
                 * @phpstan-ignore property.notFound
                 */
                'visited' => $station->planet->visited,

                /**
                 * Updated at (UTC)
                 *
                 * @var \DateTimeInterface
                 * @phpstan-ignore property.notFound
                 */
                'updated_at' => $station->planet->updated_at,
            ];
        }

        return [];
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
                                    'type' => 'boolean',
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
        ],
    ])]
    public function modelCreate(): mixed
    {
        return Planet::create([
            'name' => 'Earth',
            'diameter' => 12345,
            'visited' => true,
        ]);
    }
}

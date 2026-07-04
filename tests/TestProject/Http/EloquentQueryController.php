<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\AnnotatedPlanet;
use AutoDoc\Laravel\Tests\TestProject\Models\ClassifiedPlanet;
use AutoDoc\Laravel\Tests\TestProject\Models\LabeledPlanet;
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
        return SpaceStation::query()
            ->get()
            /** @phpstan-ignore argument.unresolvableType, method.unresolvableReturnType */
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
            'first' => SpaceStation::query()->latest()->first(),
            'firstOrFail' => SpaceStation::query()->oldest()->firstOrFail(),
        ];
    }


    /**
     * First and firstOrFail static
     */
    #[ExpectedOperationSchema([
        'summary' => 'First and firstOrFail static',
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
                                    'required' => [
                                        'id',
                                        'name',
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
                                            'required' => [
                                                'id',
                                                'name',
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
        return SpaceStation::where('created_at', '>', now()->subYear())
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn ($station) => $station->name !== null)
            /** @phpstan-ignore argument.unresolvableType, method.unresolvableReturnType */
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


    /**
     * Model setAttribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'standalone' => [
                                    'type' => 'string',
                                    'const' => 'Gaia',
                                ],
                                'chained' => [
                                    'type' => 'integer',
                                    'const' => 42,
                                ],
                            ],
                            'required' => [
                                'standalone',
                                'chained',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttribute(): mixed
    {
        $planet = Planet::firstOrFail();
        $planet->setAttribute('display_name', 'Gaia');

        return [
            /** @phpstan-ignore property.notFound */
            'standalone' => $planet->display_name,
            /** @phpstan-ignore property.nonObject */
            'chained' => Planet::firstOrFail()->setAttribute(key: 'score', value: 42)->score,
        ];
    }


    /**
     * Model setAttribute value types
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute value types',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'as_float' => [
                                    'type' => 'number',
                                    'const' => 1.5,
                                    'format' => 'float',
                                ],
                                'as_bool' => [
                                    'type' => 'boolean',
                                ],
                                'as_string' => [
                                    'type' => 'string',
                                    'const' => 'text',
                                ],
                            ],
                            'required' => [
                                'as_float',
                                'as_bool',
                                'as_string',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttributeValueTypes(): mixed
    {
        // Several attributes set on one variable across separate statements all
        // stick; each value type renders on its own (a literal bool carries no const).
        $planet = Planet::firstOrFail();
        $planet->setAttribute('as_float', 1.5);
        $planet->setAttribute('as_bool', true);
        $planet->setAttribute('as_string', 'text');

        return [
            /** @phpstan-ignore property.notFound */
            'as_float' => $planet->as_float,
            /** @phpstan-ignore property.notFound */
            'as_bool' => $planet->as_bool,
            /** @phpstan-ignore property.notFound */
            'as_string' => $planet->as_string,
        ];
    }


    /**
     * Model setAttribute alongside real columns
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute alongside real columns',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'extra' => [
                                    'type' => 'string',
                                    'const' => 'E',
                                ],
                                'real_name' => [
                                    'type' => 'string',
                                ],
                                'real_visited' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'extra',
                                'real_name',
                                'real_visited',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttributeAlongsideColumns(): mixed
    {
        // A set attribute does not disturb resolution of the model's real columns.
        $planet = Planet::firstOrFail();
        $planet->setAttribute('extra', 'E');

        return [
            /** @phpstan-ignore property.notFound */
            'extra' => $planet->extra,
            /** @phpstan-ignore property.notFound */
            'real_name' => $planet->name,
            /** @phpstan-ignore property.notFound */
            'real_visited' => $planet->visited,
        ];
    }


    /**
     * Model setAttribute preserved when the model is serialized
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute preserved when the model is serialized',
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
                                'extra_json' => [
                                    'type' => 'string',
                                    'const' => 'kept',
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
                                'extra_json',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttributeSerialized(): mixed
    {
        // Returning the whole model serializes it to JSON; the set attribute is
        // kept alongside the model's real columns.
        $planet = Planet::firstOrFail();
        $planet->setAttribute('extra_json', 'kept');

        return $planet;
    }


    /**
     * Model setAttribute with a JSON path key
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute with a JSON path key',
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
    public function modelSetAttributeJsonPath(): mixed
    {
        // Laravel routes `->` keys into a JSON column write, so no attribute
        // named `options->theme` may be fabricated on the model shape.
        $planet = Planet::firstOrFail();
        $planet->setAttribute('options->theme', 'dark');

        return $planet;
    }


    /**
     * Model setAttribute honors hidden and visible
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute honors hidden and visible',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'nickname' => [
                                    'type' => 'string',
                                    'const' => 'N',
                                ],
                            ],
                            'required' => [
                                'name',
                                'nickname',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttributeVisibility(): mixed
    {
        // Serialization applies the model's $visible whitelist and $hidden list
        // to set attributes, same as Laravel's getArrayableItems().
        $planet = ClassifiedPlanet::firstOrFail();
        $planet->setAttribute('nickname', 'N');
        $planet->setAttribute('secret_token', 'x');
        $planet->setAttribute('unlisted', 'y');

        return $planet;
    }


    /**
     * Model setAttribute on cast and mutated attributes
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute on cast and mutated attributes',
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
                                'slug' => [
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
                                'slug',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttributeCastAttributes(): mixed
    {
        // Laravel transforms values assigned to cast or mutated attributes on
        // write, so the assigned literal must not override the attribute type:
        // `visited` keeps its boolean cast type and `slug` (a set mutator with
        // no column) is present without a value type.
        $planet = Planet::firstOrFail();
        $planet->setAttribute('visited', 'yes');
        $planet->setAttribute('slug', 15);

        return $planet;
    }


    /**
     * Custom toArray building on parent::toArray
     */
    #[ExpectedOperationSchema([
        'summary' => 'Custom toArray building on parent::toArray',
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
                                'kind' => [
                                    'type' => 'string',
                                    'const' => 'planet',
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
                                'kind',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelCustomToArrayParent(): mixed
    {
        // `parent::toArray()` resolves to the model's attributes shape, so the
        // custom toArray merge keeps all columns plus the extra key.
        return AnnotatedPlanet::firstOrFail();
    }


    /**
     * Custom toArray building on attributesToArray
     */
    #[ExpectedOperationSchema([
        'summary' => 'Custom toArray building on attributesToArray',
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
                                'label' => [
                                    'type' => 'string',
                                    'const' => 'L',
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
                                'label',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelCustomToArrayAttributes(): mixed
    {
        // `$this->attributesToArray()` resolves to the model's attributes
        // shape inside a custom toArray body.
        return LabeledPlanet::firstOrFail();
    }


    /**
     * Model getAttribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model getAttribute',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'column' => [
                                    'type' => 'string',
                                ],
                                'cast' => [
                                    'type' => 'boolean',
                                ],
                                'set' => [
                                    'type' => 'string',
                                    'const' => 'Gaia',
                                ],
                                'accessor' => [
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
                            ],
                            'required' => [
                                'column',
                                'cast',
                                'set',
                                'accessor',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelGetAttribute(): mixed
    {
        // getAttribute() resolves like the property read $model->key, including
        // an attribute set earlier on the same variable.
        $planet = Planet::firstOrFail();
        $planet->setAttribute('display_name', 'Gaia');

        return [
            'column' => Planet::firstOrFail()->getAttribute('name'),
            'cast' => Planet::firstOrFail()->getAttribute('visited'),
            'set' => $planet->getAttribute('display_name'),
            'accessor' => SpaceStation::firstOrFail()->getAttribute('coordinates'),
        ];
    }


    /**
     * Model array conversion keeps a set attribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model array conversion keeps a set attribute',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'to_array' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                        ],
                                        'nickname' => [
                                            'type' => 'string',
                                            'const' => 'N',
                                        ],
                                    ],
                                    'required' => [
                                        'name',
                                        'nickname',
                                    ],
                                ],
                                'attributes_to_array' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                        ],
                                        'nickname' => [
                                            'type' => 'string',
                                            'const' => 'N',
                                        ],
                                    ],
                                    'required' => [
                                        'name',
                                        'nickname',
                                    ],
                                ],
                            ],
                            'required' => [
                                'to_array',
                                'attributes_to_array',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelArrayConversionAfterSetAttribute(): mixed
    {
        // toArray()/attributesToArray() serialize the variable's resolved shape,
        // so a set attribute is kept (honoring $visible/$hidden).
        $planet = ClassifiedPlanet::firstOrFail();
        $planet->setAttribute('nickname', 'N');

        return [
            'to_array' => $planet->toArray(),
            'attributes_to_array' => $planet->attributesToArray(),
        ];
    }


    /**
     * Builder scalar finishers
     */
    #[ExpectedOperationSchema([
        'summary' => 'Builder scalar finishers',
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
                                'staticExists' => [
                                    'type' => 'boolean',
                                ],
                                'staticSum' => [
                                    'type' => 'number',
                                ],
                            ],
                            'required' => [
                                'count',
                                'exists',
                                'doesntExist',
                                'sum',
                                'avg',
                                'staticExists',
                                'staticSum',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function builderScalarFinishers(): JsonResponse
    {
        return response()->json([
            'count' => Planet::where('visited', true)->count(),
            'exists' => Planet::where('visited', true)->exists(),
            'doesntExist' => Planet::where('visited', true)->doesntExist(),
            'sum' => Planet::query()->sum('diameter'),
            'avg' => Planet::query()->avg('diameter'),
            // Direct static calls (no ->query()/->where() prefix).
            'staticExists' => Planet::exists(),
            'staticSum' => Planet::sum('diameter'),
        ]);
    }


    /**
     * Collection aggregates and passthrough methods
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection aggregates and passthrough methods',
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
                                'avg' => [
                                    'type' => [
                                        'number',
                                        'null',
                                    ],
                                ],
                                'isEmpty' => [
                                    'type' => 'boolean',
                                ],
                                'isNotEmpty' => [
                                    'type' => 'boolean',
                                ],
                                'containsName' => [
                                    'type' => 'boolean',
                                ],
                                'names' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'a',
                                            'b',
                                        ],
                                    ],
                                ],
                                'namesString' => [
                                    'type' => 'string',
                                ],
                            ],
                            'required' => [
                                'count',
                                'avg',
                                'isEmpty',
                                'isNotEmpty',
                                'containsName',
                                'names',
                                'namesString',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function collectionAggregates(): JsonResponse
    {
        $items = collect([
            [
                'name' => 'a',
                'price' => 10,
            ],
            [
                'name' => 'b',
                'price' => 20,
            ],
        ]);

        return response()->json([
            'count' => $items->count(),
            'avg' => $items->avg('price'),
            'isEmpty' => $items->isEmpty(),
            'isNotEmpty' => $items->isNotEmpty(),
            'containsName' => $items->contains('name', 'a'),
            'names' => $items->sortDesc()->unique('name')->reverse()->slice(0, 2)->each(fn ($item) => $item)->pluck('name'),
            'namesString' => $items->implode('name', ', '),
        ]);
    }
}

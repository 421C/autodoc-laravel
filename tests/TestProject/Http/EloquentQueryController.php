<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\AnnotatedPlanet;
use AutoDoc\Laravel\Tests\TestProject\Models\AttributedPlanet;
use AutoDoc\Laravel\Tests\TestProject\Models\CastedPlanet;
use AutoDoc\Laravel\Tests\TestProject\Models\ClassifiedPlanet;
use AutoDoc\Laravel\Tests\TestProject\Models\LabeledPlanet;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
use AutoDoc\Laravel\Tests\TestProject\Models\SpaceStation;
use Illuminate\Http\JsonResponse;
use stdClass;

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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
     * Model setAttribute with a non-literal key inside a loop
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute with a non-literal key inside a loop',
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelSetAttributeNonLiteralKeyInLoop(): mixed
    {
        // Every iteration passes the loop variable as the key, so it is never a
        // single string literal; no attribute name can be resolved and the model
        // keeps only its real columns.
        $planet = Planet::firstOrFail();

        $extras = [
            'label' => 'A',
            'tag' => 'B',
            'note' => 'C',
        ];

        foreach ($extras as $key => $value) {
            $planet->setAttribute($key, $value);
        }

        return $planet;
    }


    /**
     * Model setAttribute applied conditionally inside a loop
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute applied conditionally inside a loop',
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
                                'flag_a' => [
                                    'type' => 'boolean',
                                ],
                                'flag_b' => [
                                    'type' => 'string',
                                    'const' => 'yes',
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelSetAttributeConditionalInLoop(): mixed
    {
        $planet = Planet::firstOrFail();

        $flags = ['a', 'b', 'c'];

        foreach ($flags as $flag) {
            if ($flag === 'a') {
                $planet->setAttribute('flag_a', true);

            } elseif ($flag === 'b') {
                $planet->setAttribute('flag_b', 'yes');
            }
        }

        return $planet;
    }


    /**
     * Model setAttribute applied only in one branch of a condition
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute applied only in one branch of a condition',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'badge' => [
                                    'type' => 'string',
                                    'const' => 'Visited',
                                ],
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelSetAttributeConditionalBranch(): mixed
    {
        $planet = Planet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        if ($planet->visited) {
            $planet->setAttribute('badge', 'Visited');
        }

        return $planet;
    }


    /**
     * Model setAttribute on an array element receiver
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute on an array element receiver',
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
                                'flag' => [
                                    'type' => 'boolean',
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
                                'flag',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelSetAttributeOnArrayElementReceiver(): mixed
    {
        $planets = [Planet::firstOrFail(), Planet::firstOrFail()];

        $planets[0]->setAttribute('flag', true);

        return $planets[0];
    }


    /**
     * Model setAttribute on a property fetch receiver
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute on a property fetch receiver',
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
                                'note' => [
                                    'type' => 'string',
                                    'const' => 'N',
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
                                'note',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelSetAttributeOnPropertyFetchReceiver(): mixed
    {
        $holder = new stdClass();
        $holder->planet = Planet::firstOrFail();

        $holder->planet->setAttribute('note', 'N');

        return $holder->planet;
    }


    /**
     * Model setAttribute inside a Collection each() closure
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute inside a Collection each() closure',
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
                                    'flag' => [
                                        'type' => 'boolean',
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
                                    'flag',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttributeInEachCallback(): mixed
    {
        $planets = Planet::all();

        $planets->each(function ($planet) {
            $planet->setAttribute('flag', true);
        });

        return $planets;
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
                                    'description' => [
                                        'type' => 'string',
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
                                            'has_met_aliens' => [
                                                'type' => 'boolean',
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
        ],
    ])]
    public function modelRelationSetAttributeInEachCallback(): mixed
    {
        $stations = SpaceStation::with('planet')->get();

        if (rand(0, 1)) {
            $stations->each(function ($station) {
                $station->planet?->setAttribute('has_met_aliens', false);
            });
        }

        return $stations;
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
                                'has_met_aliens' => [
                                    'type' => 'boolean',
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
                                'has_met_aliens',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelRelationNullsafeSetAttributeReturn(): mixed
    {
        $station = SpaceStation::with('planet')->firstOrFail();

        return $station->planet?->setAttribute('has_met_aliens', false);
    }


    /**
     * Model setAttribute inside a Collection each() arrow function
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute inside a Collection each() arrow function',
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
                                    'label' => [
                                        'type' => 'string',
                                        'const' => 'X',
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
        ],
    ])]
    public function modelSetAttributeInEachArrowFunction(): mixed
    {
        $planets = Planet::all();

        $planets->each(fn ($planet) => $planet->setAttribute('label', 'X'));

        return $planets;
    }


    /**
     * Model setAttribute inside each(), returning each()'s own return value
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute inside each(), returning each()\'s own return value',
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
                                    'flag' => [
                                        'type' => 'boolean',
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
                                    'flag',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelSetAttributeEachReturnValue(): mixed
    {
        return Planet::all()->each(function ($planet) {
            $planet->setAttribute('flag', true);
        });
    }


    /**
     * Model direct attribute assignment
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model direct attribute assignment',
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
                                'as_float' => [
                                    'type' => 'number',
                                    'const' => 1.5,
                                    'format' => 'float',
                                ],
                                'as_bool' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'standalone',
                                'as_float',
                                'as_bool',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelDirectAssignment(): mixed
    {
        // `$model->key = $value` goes through Laravel's __set into
        // setAttribute(), so it behaves exactly like a setAttribute() call.
        $planet = Planet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        $planet->display_name = 'Gaia';
        /** @phpstan-ignore property.notFound */
        $planet->as_float = 1.5;
        /** @phpstan-ignore property.notFound */
        $planet->as_bool = true;

        return [
            'standalone' => $planet->display_name,
            'as_float' => $planet->as_float,
            'as_bool' => $planet->as_bool,
        ];
    }


    /**
     * Model direct assignment preserved when the model is serialized
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model direct assignment preserved when the model is serialized',
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelDirectAssignmentSerialized(): mixed
    {
        // Returning the whole model serializes it to JSON; the directly
        // assigned attribute is kept alongside the model's real columns.
        $planet = Planet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        $planet->extra_json = 'kept';

        return $planet;
    }


    /**
     * Model direct assignment honors hidden and visible
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model direct assignment honors hidden and visible',
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelDirectAssignmentVisibility(): mixed
    {
        // Serialization applies the model's $visible whitelist and $hidden list
        // to directly assigned attributes, same as Laravel's getArrayableItems().
        $planet = ClassifiedPlanet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        $planet->nickname = 'N';
        /** @phpstan-ignore property.notFound */
        $planet->secret_token = 'x';
        /** @phpstan-ignore property.notFound */
        $planet->unlisted = 'y';

        return $planet;
    }


    /**
     * Model direct assignment on cast and mutated attributes
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model direct assignment on cast and mutated attributes',
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelDirectAssignmentCastAttributes(): mixed
    {
        // Laravel transforms values assigned to cast or mutated attributes on
        // write, so the assigned literal must not override the attribute type:
        // `visited` keeps its boolean cast type and `slug` (a set mutator with
        // no column) is present without a value type.
        $planet = Planet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        $planet->visited = 'yes';
        /** @phpstan-ignore property.notFound */
        $planet->slug = 15;

        return $planet;
    }


    /**
     * Model direct assignment on an array element receiver
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model direct assignment on an array element receiver',
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
                                'flag' => [
                                    'type' => 'boolean',
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
                                'flag',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelDirectAssignmentOnArrayElementReceiver(): mixed
    {
        $planets = [Planet::firstOrFail(), Planet::firstOrFail()];

        /** @phpstan-ignore property.notFound */
        $planets[0]->flag = true;

        return $planets[0];
    }


    /**
     * Model direct assignment on a property fetch receiver
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model direct assignment on a property fetch receiver',
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
                                'note' => [
                                    'type' => 'string',
                                    'const' => 'N',
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
                                'note',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelDirectAssignmentOnPropertyFetchReceiver(): mixed
    {
        $holder = new stdClass();
        $holder->planet = Planet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        $holder->planet->note = 'N';

        return $holder->planet;
    }


    /**
     * Model array conversion keeps a directly assigned attribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model array conversion keeps a directly assigned attribute',
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelArrayConversionAfterDirectAssignment(): mixed
    {
        // toArray()/attributesToArray() serialize the variable's resolved shape,
        // so a directly assigned attribute is kept (honoring $visible/$hidden).
        $planet = ClassifiedPlanet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        $planet->nickname = 'N';
        /** @phpstan-ignore property.notFound */
        $planet->secret_token = 'x';

        return [
            'to_array' => $planet->toArray(),
            'attributes_to_array' => $planet->attributesToArray(),
        ];
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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
            404 => [
                'description' => '',
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


    /**
     * Single-column builder finishers
     */
    #[ExpectedOperationSchema([
        'summary' => 'Single-column builder finishers',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'value' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'min' => [
                                    'type' => [
                                        'number',
                                        'null',
                                    ],
                                    'format' => 'float',
                                ],
                                'max' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                            ],
                            'required' => [
                                'value',
                                'min',
                                'max',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function singleColumnFinishers(): JsonResponse
    {
        return response()->json([
            // `value`/`min`/`max` resolve to their column's type, nullable for the
            // empty-result case; `min`/`max` also work as direct static calls.
            'value' => Planet::where('visited', true)->value('name'),
            'min' => Planet::query()->min('diameter'),
            'max' => Planet::max('id'),
        ]);
    }


    /**
     * Collection scalar and key methods
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection scalar and key methods',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'min' => [
                                    'type' => [
                                        'number',
                                        'null',
                                    ],
                                ],
                                'max' => [
                                    'type' => [
                                        'number',
                                        'null',
                                    ],
                                ],
                                'median' => [
                                    'type' => [
                                        'number',
                                        'null',
                                    ],
                                ],
                                'every' => [
                                    'type' => 'boolean',
                                ],
                                'doesntContain' => [
                                    'type' => 'boolean',
                                ],
                                'join' => [
                                    'type' => 'string',
                                ],
                                'keys' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ],
                            'required' => [
                                'min',
                                'max',
                                'median',
                                'every',
                                'doesntContain',
                                'join',
                                'keys',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function collectionScalarAndKeyMethods(): JsonResponse
    {
        $prices = collect([10, 20, 30]);

        return response()->json([
            'min' => $prices->min(),
            'max' => $prices->max(),
            'median' => $prices->median(),
            'every' => $prices->every(fn ($price) => $price > 0),
            'doesntContain' => $prices->doesntContain(5),
            'join' => $prices->join(', '),
            'keys' => $prices->keys(),
        ]);
    }


    /**
     * Collection sole and firstOrFail keep the item shape
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection sole and firstOrFail keep the item shape',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'sole' => [
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
                                'firstOrFail' => [
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
                            'required' => [
                                'sole',
                                'firstOrFail',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function collectionSoleAndWhere(): JsonResponse
    {
        // `where` preserves the collection item shape; `sole`/`firstOrFail`
        // return that item without the `null` branch that `first` adds.
        $planets = Planet::select('id', 'name')->get();

        return response()->json([
            'sole' => $planets->where('visited', true)->sole(),
            'firstOrFail' => $planets->firstOrFail(),
        ]);
    }


    /**
     * Collection each() ignores by-value parameter reassignment
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection each() ignores by-value parameter reassignment',
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
    public function modelEachCallbackReassignmentIgnored(): mixed
    {
        // each() passes items by value, so reassigning the callback parameter
        // does not change the collection; items keep their plain model shape.
        $planets = Planet::all();

        $planets->each(function ($planet) {
            $planet = 'changed';
        });

        return $planets;
    }


    /**
     * Collection each() callback returning false makes the mutation optional
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection each() callback returning false makes the mutation optional',
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
                                    'flag' => [
                                        'type' => 'boolean',
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
        ],
    ])]
    public function modelEachCallbackEarlyStop(): mixed
    {
        // Returning false stops Laravel's iteration, so the mutation is not
        // guaranteed on every item: `flag` becomes optional.
        $planets = Planet::all();

        $planets->each(function ($planet) {
            $planet->setAttribute('flag', true);

            return false;
        });

        return $planets;
    }


    /**
     * Model getAttribute reads a hidden attribute set via setAttribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model getAttribute reads a hidden attribute set via setAttribute',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => [
                                    'type' => 'string',
                                    'const' => 'x',
                                ],
                            ],
                            'required' => [
                                'token',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelGetAttributeHiddenAfterSet(): mixed
    {
        // $hidden/$visible affect serialization, not direct access: a hidden
        // attribute set via setAttribute() is still readable via getAttribute().
        $planet = ClassifiedPlanet::firstOrFail();
        $planet->setAttribute('secret_token', 'x');

        return [
            'token' => $planet->getAttribute('secret_token'),
        ];
    }


    /**
     * Model direct assignment of null to a cast attribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model direct assignment of null to a cast attribute',
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
                                    'type' => 'null',
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
            ],
        ],
    ])]
    public function modelDirectAssignmentNullCast(): mixed
    {
        // Assigning null to a cast attribute serializes as null, not the cast type.
        $planet = Planet::firstOrFail();

        /** @phpstan-ignore property.notFound */
        $planet->visited = null;

        return $planet;
    }


    /**
     * Model setAttribute of null to a cast attribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute of null to a cast attribute',
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
                                    'type' => 'null',
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
            ],
        ],
    ])]
    public function modelSetAttributeNullCast(): mixed
    {
        // setAttribute(null) on a cast attribute serializes as null too.
        $planet = Planet::firstOrFail();
        $planet->setAttribute('visited', null);

        return $planet;
    }


    /**
     * Model setAttribute on accessor attributes
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute on accessor attributes',
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
                                'description' => [
                                    'type' => 'string',
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
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelSetAttributeAccessorAttributes(): mixed
    {
        // attributesToArray() pipes every attribute with a get accessor through
        // it, so assigned literals must not survive serialization: `name` keeps
        // the getNameAttribute() return type, and `id` keeps the accessor union
        // even though its int cast matches the assigned value.
        $station = SpaceStation::firstOrFail();
        $station->setAttribute('name', 'raw');
        $station->id = 500;

        return $station;
    }


    /**
     * Model setAttribute of null to a date attribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute of null to a date attribute',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'created_at' => [
                                    'type' => 'null',
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
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function modelSetAttributeNullDate(): mixed
    {
        // Laravel skips fromDateTime() for null on write and skips date
        // serialization for null values, so an assigned null survives on a
        // date attribute.
        $planet = Planet::firstOrFail();
        $planet->setAttribute('created_at', null);

        return $planet;
    }


    /**
     * Model setAttribute of null to a class-caster attribute
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model setAttribute of null to a class-caster attribute',
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
                                    'type' => 'string',
                                    'enum' => [
                                        'on',
                                        'off',
                                    ],
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
            ],
        ],
    ])]
    public function modelSetAttributeNullClassCaster(): mixed
    {
        // Unlike primitive casts, a CastsAttributes class caster receives null
        // in get()/set() and can transform it, so the assigned null must not
        // override the caster's return type.
        $planet = CastedPlanet::firstOrFail();
        $planet->setAttribute('visited', null);

        return $planet;
    }


    /**
     * Collection each() returning false keeps original types of changed attributes
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection each() returning false keeps original types of changed attributes',
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
    public function modelEachCallbackEarlyStopExistingProperty(): mixed
    {
        // Returning false stops Laravel's iteration, so an existing attribute
        // changed in the callback is only changed on some items: the type
        // becomes the union of the original and the assigned value, which
        // collapses back to the plain column type.
        $planets = Planet::all();

        $planets->each(function ($planet) {
            $planet->setAttribute('name', 'X');

            return false;
        });

        return $planets;
    }


    /**
     * Collection each() on a derived collection leaves the source untouched
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection each() on a derived collection leaves the source untouched',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'flagged' => [
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
                                            'flag' => [
                                                'type' => 'boolean',
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
                                            'flag',
                                        ],
                                    ],
                                ],
                                'original' => [
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
                            'required' => [
                                'flagged',
                                'original',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelEachReturnValueDoesNotMutateSource(): mixed
    {
        // take() passes the resolved collection type through, so each() must
        // clone before attaching the mutated item type: the source variable
        // keeps its plain item shape.
        $planets = Planet::all();

        return [
            'flagged' => $planets->take(2)->each(fn ($planet) => $planet->setAttribute('flag', true)),
            'original' => $planets,
        ];
    }


    /**
     * Collection each() ignores mutations after a same-class parameter reassignment
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection each() ignores mutations after a same-class parameter reassignment',
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
    public function modelEachCallbackSameClassReassignmentIgnored(): mixed
    {
        // Reassigning the parameter to another model of the same class breaks
        // the reference to the collection item, so the mutation after it does
        // not reach the collection; items keep their plain model shape.
        $planets = Planet::all();

        $planets->each(function ($planet) {
            $planet = Planet::firstOrFail();
            $planet->setAttribute('flag', true);
        });

        return $planets;
    }


    /**
     * Collection each() keeps mutations applied before a parameter reassignment
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection each() keeps mutations applied before a parameter reassignment',
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
                                    'flag' => [
                                        'type' => 'boolean',
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
                                    'flag',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function modelEachCallbackMutationBeforeReassignment(): mixed
    {
        // The mutation runs while the parameter still references the original
        // item, so it survives the later reassignment and reaches every item.
        $planets = Planet::all();

        $planets->each(function ($planet) {
            $planet->setAttribute('flag', true);
            $planet = 'done';
        });

        return $planets;
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
            ],
        ],
    ])]
    public function soleQueryFinisher(): mixed
    {
        return Planet::query()->where('visited', true)->sole();
    }

    /**
     * Model configured through Eloquent attributes
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model configured through Eloquent attributes',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'string',
                                ],
                                'name' => [
                                    'type' => 'string',
                                ],
                                'display_name' => [
                                    'type' => 'string',
                                    'const' => 'Planet',
                                ],
                                'phpdoc_visible' => [
                                    'type' => 'integer',
                                ],
                                'kind' => [
                                    'type' => 'string',
                                    'const' => 'planet',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'display_name',
                                'kind',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function attributedModelSerialization(): mixed
    {
        // Laravel's own configuration attributes feed the analyzed model: #[Table]
        // drives the string primary key cast, #[Appends] adds the accessor, and
        // #[Hidden]/#[Visible] partition serialized attributes. PHPDoc @property
        // tags are partitioned by those same rules, so `parent::toArray()` keeps
        // only `phpdoc_visible` and drops `phpdoc_secret`/`phpdoc_omitted`.
        return AttributedPlanet::firstOrFail();
    }


    /**
     * Model PHPDoc property excluded from serialization is still readable
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model PHPDoc property excluded from serialization is still readable',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'secret' => [
                                    'type' => 'number',
                                    'format' => 'float',
                                ],
                                'omitted' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'secret',
                                'omitted',
                            ],
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => '',
            ],
        ],
    ])]
    public function attributedModelPhpDocHiddenRead(): mixed
    {
        // $hidden/$visible affect serialization, not direct access: PHPDoc
        // properties kept out of the serialized shape stay readable.
        $planet = AttributedPlanet::firstOrFail();

        return [
            'secret' => $planet->phpdoc_secret,
            'omitted' => $planet->phpdoc_omitted,
        ];
    }
}

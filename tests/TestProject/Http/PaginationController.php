<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
use AutoDoc\Laravel\Tests\TestProject\Models\SpaceStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Tests for paginated responses.
 */
class PaginationController
{
    /**
     * Basic pagination
     */
    #[ExpectedOperationSchema([
        'summary' => 'Basic pagination',
        'description' => '',
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'page',
                'schema' => [
                    'type' => 'integer',
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
                                'current_page' => [
                                    'type' => 'integer',
                                ],
                                'data' => [
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
                    ],
                ],
            ],
        ],
    ])]
    public function basicPagination(): JsonResponse
    {
        $rockets = Rocket::paginate(100);

        return response()->json($rockets);
    }


    /**
     * Pagination with custom page name
     */
    #[ExpectedOperationSchema([
        'summary' => 'Pagination with custom page name',
        'description' => '',
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'page_number',
                'schema' => [
                    'type' => 'integer',
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
                                'current_page' => [
                                    'type' => 'integer',
                                ],
                                'data' => [
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
                                            'size' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'size',
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
                    ],
                ],
            ],
        ],
    ])]
    public function paginationWithCustomPageName(): mixed
    {
        return SpaceStation::select('id', 'size')->paginate(50, pageName: 'page_number');
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
                                'current_page' => [
                                    'type' => 'integer',
                                ],
                                'data' => [
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
                                        ],
                                        'required' => [
                                            'id',
                                            'diameter',
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
                    ],
                ],
            ],
        ],
    ])]
    public function paginationWithDynamicColumns(): mixed
    {
        return Planet::query()->paginate(null, ['planets.id', 'diameter'], strtoupper('page'));
    }


    /**
     * @return LengthAwarePaginator<int, int>
     */
    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'current_page' => [
                                    'type' => 'integer',
                                ],
                                'data' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
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
                    ],
                ],
            ],
        ],
    ])]
    public function paginatorFromReturnType(): LengthAwarePaginator
    {
        /** @phpstan-ignore return.type */
        return null;
    }


    /**
     * @phpstan-ignore missingType.generics
     */
    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'p',
                'schema' => [
                    'type' => 'integer',
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
                                                'type' => 'boolean',
                                            ],
                                            'y' => [
                                                'type' => 'string',
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
                    ],
                ],
            ],
        ],
    ])]
    public function paginationWithAllColumns(): LengthAwarePaginator
    {
        return Planet::query()->paginate(10, ['*', 'x.y'], 'p', null, null);
    }
}

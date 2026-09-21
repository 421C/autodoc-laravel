<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use Illuminate\Http\Request;

class BuilderConditionalsController
{
    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'a',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'b',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'c',
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
                                [
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
                                [
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
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'required' => [
                                            'name',
                                            'diameter',
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
                                [
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
                                [
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
                                [
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
                                            'name' => [
                                                'type' => 'string',
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
            ],
        ],
    ])]
    public function chainedConditions(Request $request): mixed
    {
        return Planet::query()
            ->when($request->boolean('a'), fn ($query) => $query->select('id'))
            ->when($request->boolean('b'), fn ($query) => $query->addSelect('name'))
            ->when($request->boolean('c'), fn ($query) => $query->addSelect('diameter'))
            ->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'f1',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'f2',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'f3',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'f4',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'f5',
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
    public function filteringConditions(Request $request): mixed
    {
        return Planet::query()
            ->when($request->boolean('f1'), fn ($query) => $query->where('name', 'a'))
            ->when($request->boolean('f2'), fn ($query) => $query->where('name', 'b'))
            ->when($request->boolean('f3'), fn ($query) => $query->orderBy('name'))
            ->when($request->boolean('f4'), fn ($query) => $query->where('name', 'd'))
            ->when($request->boolean('f5'), fn ($query) => $query->where('name', 'e'))
            ->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'expand',
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
                                            'rockets',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function conditionalEagerLoad(Request $request): mixed
    {
        return Planet::query()
            ->when($request->boolean('expand'), fn ($query) => $query->with('rockets'))
            ->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'a',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'b',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'c',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'd',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'e',
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
                                                'type' => 'boolean',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
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
                                            'diameter',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
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
                                            'name',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
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
                                            'name',
                                            'diameter',
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
                                        ],
                                    ],
                                ],
                                [
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
                                            'diameter',
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
                                        ],
                                    ],
                                ],
                                [
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
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function conditionsBeyondTheVariantLimit(Request $request): mixed
    {
        return Planet::query()
            ->when($request->boolean('a'), fn ($query) => $query->addSelect('id'))
            ->when($request->boolean('b'), fn ($query) => $query->addSelect('name'))
            ->when($request->boolean('c'), fn ($query) => $query->addSelect('diameter'))
            ->when($request->boolean('d'), fn ($query) => $query->addSelect('visited'))
            ->when($request->boolean('e'), fn ($query) => $query->addSelect('updated_at'))
            ->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'f1',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'f2',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'f3',
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
    public function filteringIfStatements(Request $request): mixed
    {
        $query = Planet::query();

        if ($request->boolean('f1')) {
            $query->where('name', 'a');
        }

        if ($request->boolean('f2')) {
            $query->where('name', 'b');
        }

        if ($request->boolean('f3')) {
            $query->orderBy('name');
        }

        return $query->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'a',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'b',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'c',
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
                                [
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
                                [
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
                                [
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
                                [
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
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'required' => [
                                            'name',
                                            'diameter',
                                        ],
                                    ],
                                ],
                                [
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
                                            'name' => [
                                                'type' => 'string',
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
            ],
        ],
    ])]
    public function sequentialIfStatements(Request $request): mixed
    {
        $query = Planet::query();

        if ($request->boolean('a')) {
            $query->select('id');
        }

        if ($request->boolean('b')) {
            $query->addSelect('name');
        }

        if ($request->boolean('c')) {
            $query->addSelect('diameter');
        }

        return $query->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'a',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'b',
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
                                [
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
                                [
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
            ],
        ],
    ])]
    public function conditionBehindVariableAssignment(Request $request): mixed
    {
        $query = Planet::query()->when($request->boolean('a'), fn ($inner) => $inner->select('id'));

        if ($request->boolean('b')) {
            $query->addSelect('name');
        }

        return $query->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'a',
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
                    ],
                ],
            ],
        ],
    ])]
    public function conditionOnBuilderVariable(Request $request): mixed
    {
        $query = Planet::query();

        $query->when($request->boolean('a'), fn ($inner) => $inner->addSelect('id'));

        return $query->get();
    }


    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'a',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'b',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'c',
                'schema' => [
                    'type' => 'boolean',
                ],
            ],
            [
                'in' => 'query',
                'name' => 'd',
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
                            'anyOf' => [
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
                                            ],
                                            'visited' => [
                                                'type' => 'boolean',
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'visited',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'diameter',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'diameter' => [
                                                'type' => 'number',
                                                'format' => 'float',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
                                            ],
                                            'visited' => [
                                                'type' => 'boolean',
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'diameter',
                                            'visited',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
                                            ],
                                            'visited' => [
                                                'type' => 'boolean',
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                            'visited',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                            'diameter',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'beacons' => [
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
                                            'total' => [
                                                'type' => 'integer',
                                                'minimum' => 0,
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
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function conditionalRawSelectAndEagerLoads(Request $request): mixed
    {
        return Planet::query()
            ->select('id')
            ->when($request->boolean('a'), fn ($query) => $query->addSelect('name'))
            ->when($request->boolean('b'), fn ($query) => $query->addSelect('diameter'))
            ->when($request->boolean('c'), fn ($query) => $query->addSelect('visited'))
            ->when(
                $request->boolean('d'),
                fn ($query) => $query->selectRaw('count(*) as total')->with(['rockets:id', 'beacons:id']),
            )
            ->without('rockets')
            ->get();
    }
}

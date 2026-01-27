<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\User;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserCollection;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResource;
use AutoDoc\Laravel\Tests\TestProject\Entities\UserResourceCollection;

/**
 * Tests for Laravel Resource and ResourceCollection responses.
 */
class ResourceController
{
    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
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
                                'email' => [
                                    'type' => 'string',
                                ],
                                'created_at' => [
                                    'format' => 'date-time',
                                    'type' => 'string',
                                ],
                                'updated_at' => [
                                    'format' => 'date-time',
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'status' => [
                                    'description' => '[StateEnum](#/schemas/StateEnum)',
                                    'enum' => [
                                        1,
                                        2,
                                    ],
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'email',
                                'created_at',
                                'updated_at',
                                'status',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function singleResource(): mixed
    {
        return new UserResource(new User);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
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
                                            'created_at' => [
                                                'format' => 'date-time',
                                                'type' => 'string',
                                            ],
                                            'email' => [
                                                'type' => 'string',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'status' => [
                                                'description' => '[StateEnum](#/schemas/StateEnum)',
                                                'enum' => [
                                                    1,
                                                    2,
                                                ],
                                                'type' => 'integer',
                                            ],
                                            'updated_at' => [
                                                'format' => 'date-time',
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                            'email',
                                            'created_at',
                                            'updated_at',
                                            'status',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function resourceCollection(): mixed
    {
        return UserResource::collection([new User]);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'users' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'format' => 'date-time',
                                                'type' => 'string',
                                            ],
                                            'email' => [
                                                'type' => 'string',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'status' => [
                                                'description' => '[StateEnum](#/schemas/StateEnum)',
                                                'enum' => [
                                                    1,
                                                    2,
                                                ],
                                                'type' => 'integer',
                                            ],
                                            'updated_at' => [
                                                'format' => 'date-time',
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                            'email',
                                            'created_at',
                                            'updated_at',
                                            'status',
                                        ],
                                    ],
                                ],
                                'count' => [
                                    'description' => 'Number of users returned.',
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'users',
                                'count',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function customResourceCollection(): mixed
    {
        return new UserResourceCollection([new User]);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'created_at' => [
                                        'format' => 'date-time',
                                        'type' => 'string',
                                    ],
                                    'email' => [
                                        'type' => 'string',
                                    ],
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'updated_at' => [
                                        'format' => 'date-time',
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],
                                ],
                                'required' => [
                                    'id',
                                    'name',
                                    'email',
                                    'created_at',
                                    'updated_at',
                                ],
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function arrayableCollection(): mixed
    {
        return new UserCollection([new User]);
    }


    /**
     * Collection inside array response
     */
    #[ExpectedOperationSchema([
        'summary' => 'Collection inside array response',
        'description' => '',
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'collection' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'created_at' => [
                                                'format' => 'date-time',
                                                'type' => 'string',
                                            ],
                                            'email' => [
                                                'type' => 'string',
                                            ],
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                            'updated_at' => [
                                                'format' => 'date-time',
                                                'type' => [
                                                    'string',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                            'email',
                                            'created_at',
                                            'updated_at',
                                        ],
                                    ],
                                ],
                                'count' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'collection',
                                'count',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function collectionInsideArray(): mixed
    {
        $collection = new UserCollection([new User]);

        return [
            'collection' => $collection,
            'count' => $collection->count(),
        ];
    }
}

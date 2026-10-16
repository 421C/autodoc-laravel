<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

class ResponseController
{
    #[ExpectedOperationSchema([
        'responses' => [
            204 => [
                'description' => '',
            ],
        ],
    ])]
    public function noContent(): Response
    {
        return response()->noContent();
    }


    #[ExpectedOperationSchema([
        'responses' => [
            418 => [
                'description' => '',
            ],
        ],
    ])]
    public function noContentWithCustomStatus(): Response
    {
        return response()->noContent(status: 418);
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
                                'status' => [
                                    'type' => 'string',
                                    'const' => 'ok',
                                ],
                            ],
                            'required' => [
                                'status',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function streamJson(): mixed
    {
        return response()->streamJson(['status' => 'ok']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            202 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'queued' => [
                                    'type' => 'boolean',
                                ],
                            ],
                            'required' => [
                                'queued',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function streamJsonWithNamedStatus(): mixed
    {
        return response()->streamJson(['queued' => true], status: 202);
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
                                'planets' => [
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
                                'planets',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function streamJsonCursor(): StreamedJsonResponse
    {
        return response()->streamJson([
            'planets' => Planet::select('id', 'name')->cursor(),
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
                            'properties' => [
                                'status' => [
                                    'type' => 'string',
                                    'const' => 'ok',
                                ],
                            ],
                            'required' => [
                                'status',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function streamJsonFromInjectedFactory(ResponseFactory $responses): mixed
    {
        return $responses->streamJson(['status' => 'ok']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            201 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => [
                                    'type' => 'string',
                                    'const' => 'created',
                                ],
                            ],
                            'required' => [
                                'status',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function jsonFromInjectedFactory(ResponseFactory $responses): mixed
    {
        return $responses->json(['status' => 'created'], 201);
    }
}

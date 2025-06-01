<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Http\Request;


class InvokableController
{
    #[ExpectedOperationSchema([
        'requestBody' => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'name',
                        ],
                        'type' => 'object',
                    ],
                ],
            ],
            'description' => '',
            'required' => false,
        ],
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'const' => 'yo',
                            'type' => 'string',
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])]
    public function __invoke(Request $request): string
    {
        $request->validate([
            'name' => 'required',
        ]);

        return 'yo';
    }
}

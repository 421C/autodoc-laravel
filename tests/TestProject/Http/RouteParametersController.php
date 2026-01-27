<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\StateEnum;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
use Illuminate\Http\Request;

/**
 * Tests for route parameters: model binding, enum binding, path parameters.
 */
class RouteParametersController
{
    /**
     * Model binding
     */
    #[ExpectedOperationSchema([
        'summary' => 'Model binding',
        'description' => '',
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'rocket',
                'required' => true,
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
    ])]
    public function modelBinding(Rocket $rocket): mixed
    {
        return $rocket;
    }


    /**
     * Implicit enum binding
     */
    #[ExpectedOperationSchema([
        'summary' => 'Implicit enum binding',
        'description' => '',
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'state',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                    'description' => '[StateEnum](#/schemas/StateEnum)',
                    'enum' => [
                        1,
                        2,
                    ],
                ],
            ],
        ],
    ])]
    public function enumBinding(StateEnum $state): void {}


    /**
     * Scalar path parameter with PHPDoc
     *
     * @param int $rocketId Description of the identifier.
     */
    #[ExpectedOperationSchema([
        'summary' => 'Scalar path parameter with PHPDoc',
        'description' => '',
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'rocketId',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                    'description' => 'Description of the identifier.',
                ],
            ],
        ],
    ])]
    public function scalarPathParameter(Request $request, int $rocketId): void {}
}

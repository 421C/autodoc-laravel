<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\Extensions\OperationExtension;
use AutoDoc\OpenApi\MediaType;
use AutoDoc\OpenApi\Operation;
use AutoDoc\OpenApi\Response;
use AutoDoc\Route;

/**
 * Rewrites body-less error responses with the project's standard error envelope.
 */
class ProjectExceptionResponses extends OperationExtension
{
    public function handle(Operation $operation, Route $route, Scope $scope): ?Operation
    {
        foreach ($operation->responses ?? [] as $status => $response) {
            if ((int) $status < 400 || ! ($response instanceof Response)) {
                continue;
            }

            $operation->responses[$status] = new Response(
                content: [
                    'application/json' => new MediaType(
                        type: new ObjectType(properties: [
                            'error' => (new StringType)->setRequired(true),
                            'status' => (new IntegerType)->setRequired(true),
                        ]),
                        config: $scope->config,
                    ),
                ],
            );
        }

        return $operation;
    }
}

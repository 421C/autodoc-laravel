<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallExtension;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;

/**
 * Handles `collection` static method calls on `Illuminate\Http\Resources\Json\JsonResource` class.
 */
class ResourceStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCall $methodCall, Scope $scope): ?Type
    {
        if ($methodCall->name instanceof Node\Identifier
            && $methodCall->name->name === 'collection'
            && $methodCall->class instanceof Node\Name
        ) {
            $resourceClass = $scope->getResolvedClassName($methodCall->class);

            if ($resourceClass && is_subclass_of($resourceClass, JsonResource::class)) {
                return new ObjectType([
                    'data' => new ArrayType($scope->getPhpClassInDeeperScope($resourceClass)->resolveType()),
                ]);
            }
        }

        return null;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Handles `collection` static method calls on `Illuminate\Http\Resources\Json\JsonResource` class.
 */
class ResourceStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->methodName === 'collection') {
            $resourceClass = $call->className;

            if ($resourceClass && is_subclass_of($resourceClass, JsonResource::class)) {
                return new ObjectType([
                    'data' => new ArrayType($call->scope->getPhpClassInDeeperScope($resourceClass)->resolveType()),
                ]);
            }
        }

        return null;
    }
}

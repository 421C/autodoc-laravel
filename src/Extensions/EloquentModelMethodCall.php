<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\MethodCallContext;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;

/**
 * Handles method calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCallContext $call): ?Type
    {
        if ($call->methodName !== 'toArray') {
            return null;
        }

        $varType = $call->getVarType();

        if (! ($varType instanceof ObjectType)
            || ! $varType->className
            || ! (is_subclass_of($varType->className, Model::class))
        ) {
            return null;
        }

        $scope = $call->scope;
        $phpClass = $scope->getPhpClassInDeeperScope($varType->className);

        $modelToArrayMethod = $phpClass->getMethod('toArray');

        $modelToArrayMethodReflection = $modelToArrayMethod->getReflection();
        $modelToArrayMethodDeclaringClass = $modelToArrayMethodReflection instanceof ReflectionMethod
            ? $modelToArrayMethodReflection->getDeclaringClass()->getName()
            : null;

        if ($modelToArrayMethodDeclaringClass && $modelToArrayMethodDeclaringClass !== Model::class) {
            $modelArrayRepresentation = $modelToArrayMethod->getReturnType()->unwrapType($phpClass->scope->config);

            if ($modelArrayRepresentation instanceof ArrayType) {
                if (! isset($modelArrayRepresentation->shape) && ! isset($modelArrayRepresentation->itemType)) {
                    $modelArrayRepresentation->itemType = new UnknownType;
                }

                return $modelArrayRepresentation;
            }

        } else {
            return new ArrayType(shape: $varType->properties);
        }

        return null;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;

/**
 * Handles method calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if (! ($methodCall->name instanceof Node\Identifier)) {
            return null;
        }

        $methodName = $methodCall->name->name;

        if ($methodName !== 'toArray') {
            return null;
        }

        $varType = $scope->resolveType($methodCall->var);

        if (! ($varType instanceof ObjectType)
            || ! $varType->className
            || ! (is_subclass_of($varType->className, Model::class))
        ) {
            return null;
        }

        $phpClass = $scope->getPhpClassInDeeperScope($varType->className);

        $modelToArrayMethod = $phpClass->getMethod('toArray');

        $modelToArrayMethodDeclaringClass = $modelToArrayMethod->getReflection()?->class;

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

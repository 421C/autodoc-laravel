<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodClassifier;
use AutoDoc\Laravel\QueryBuilder\QueryNavigator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Handles static calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCallContext $call): ?Type
    {
        $methodName = $call->methodName;

        if ($methodName === 'toArray' || $methodName === 'attributesToArray') {
            return $this->getModelAttributesShape($call);
        }

        if (! BuilderMethodClassifier::supportsModelStaticCall($methodName)) {
            return null;
        }

        $className = $call->className;

        if (! $className) {
            return null;
        }

        if (! is_subclass_of($className, Model::class)) {
            return null;
        }

        $scope = $call->scope;
        $node = $call->node;

        if ($methodName === 'insert') {
            return new BoolType;
        }

        if ($methodName === 'count') {
            return new IntegerType(minimum: 0);
        }

        if ($methodName === 'all') {
            $rowType = $scope->withoutScalarTypeValueMerging(function () use ($scope, $node) {
                return (new QueryNavigator($scope))->getRowType($node);
            });

            return new ArrayType(
                itemType: $rowType,
                className: Collection::class,
            );
        }

        return (new QueryNavigator($scope))->getResultType($node, $methodName);
    }


    /**
     * Resolves `parent::toArray()` / `parent::attributesToArray()` inside a
     * custom `toArray()` body. The call runs with the child's `$this`, so the
     * analyzed class supplies the attribute shape.
     */
    private function getModelAttributesShape(StaticCallContext $call): ?ArrayType
    {
        $className = $call->className;

        if (! $className || ! is_a($className, Model::class, true)) {
            return null;
        }

        $scopeClassName = $call->scope->className;

        $modelClassName = $scopeClassName && is_subclass_of($scopeClassName, Model::class)
            ? $scopeClassName
            : $className;

        return (new EloquentModel)->getModelAttributesArrayType($call->scope, $modelClassName);
    }
}

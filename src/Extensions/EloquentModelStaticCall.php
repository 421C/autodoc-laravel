<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\StaticCallContext;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallExtension;
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
        $supportedMethods = [
            'count',
            'insert',
            'insertOrIgnore',
            'insertOrThrow',
            'insertUsing',
            'insertGetId',
            'insertUsingGetId',
            'find',
            'firstWhere',
            'first',
            'firstOrFail',
            'findOrFail',
            'firstOrNew',
            'firstOrCreate',
            'updateOrCreate',
            'create',
            'all',
            'get',
            'paginate',
            'pluck',
        ];

        $methodName = $call->methodName;

        if (! in_array($methodName, $supportedMethods)) {
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
}

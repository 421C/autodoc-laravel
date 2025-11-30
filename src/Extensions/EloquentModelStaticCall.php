<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\QueryBuilder\QueryNavigator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;

/**
 * Handles static calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCall $methodCall, Scope $scope): ?Type
    {
        if (! ($methodCall->name instanceof Node\Identifier)) {
            return null;
        }

        if (! ($methodCall->class instanceof Node\Name)) {
            return null;
        }

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

        $methodName = $methodCall->name->name;

        if (! in_array($methodName, $supportedMethods)) {
            return null;
        }

        $className = $scope->getResolvedClassName($methodCall->class);

        if (! $className) {
            return null;
        }

        if (! is_subclass_of($className, Model::class)) {
            return null;
        }

        if ($methodName === 'insert') {
            return new BoolType;
        }

        if ($methodName === 'count') {
            return new IntegerType(minimum: 0);
        }

        if ($methodName === 'all') {
            $rowType = $scope->withoutScalarTypeValueMerging(function () use ($scope, $methodCall) {
                return (new QueryNavigator($scope))->getRowType($methodCall);
            });

            return new ArrayType(
                itemType: $rowType,
                className: Collection::class,
            );
        }

        return (new QueryNavigator($scope))->getResultType($methodCall, $methodName);
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\QueryBuilder\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'insert',
            'find',
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

        if ($methodName === 'all' || $methodName === 'get') {
            return new ArrayType(itemType: $scope->getPhpClassInDeeperScope($className)->resolveType());
        }

        if ($methodName === 'paginate') {
            return (new Paginator(
                paginatorPhpClass: $scope->getPhpClassInDeeperScope(LengthAwarePaginator::class),
                entryClass: $className,
            ))->resolveType();
        }

        return $scope->getPhpClassInDeeperScope($className)->resolveType();
    }
}

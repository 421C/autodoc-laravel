<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\FuncCallExtension;
use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

/**
 * Handles `collect(...)`.
 */
class CollectCall extends FuncCallExtension
{
    public function getReturnType(FuncCall $funcCall, Scope $scope): ?Type
    {
        if ($funcCall->name instanceof Node\Name && $funcCall->name->name === 'collect') {
            $dataToCollect = $funcCall->args[0]->value ?? null;

            if ($dataToCollect) {
                $collectedType = $scope->resolveType($dataToCollect);

                if ($collectedType instanceof ArrayType) {
                    $collectedType->className = Collection::class;

                    return $collectedType->convertShapeToTypePair();
                }
            }

            return new ArrayType(className: Collection::class);
        }

        return null;
    }
}

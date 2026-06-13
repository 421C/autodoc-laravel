<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\FuncCallContext;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\FuncCallExtension;
use Illuminate\Support\Collection;

/**
 * Handles `collect(...)`.
 */
class CollectCall extends FuncCallExtension
{
    public function getReturnType(FuncCallContext $call): ?Type
    {
        if ($call->functionName === 'collect') {
            if ($call->argTypes->has(0)) {
                $collectedType = $call->argTypes->get(0);

                if ($collectedType instanceof ArrayType) {
                    $collectedType->className = Collection::class;

                    return $collectedType->convertShapeToTypePair($call->scope->config);
                }
            }

            return new ArrayType(className: Collection::class);
        }

        return null;
    }
}

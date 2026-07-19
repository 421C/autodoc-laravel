<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\DataTypes\CallableType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class DatabaseTransactionStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->methodName !== 'transaction'
            || $call->className === null
            || ! is_a($call->className, DB::class, true)
            || ! $call->argTypes->has(0)
        ) {
            return null;
        }

        $callbackType = $call->argTypes->get(0);

        if (! $callbackType instanceof CallableType) {
            return null;
        }

        return $callbackType->getReturnType(
            ArgumentList::fromTypes([
                new ObjectType(className: Connection::class),
            ], $call->scope),
            $call->node,
        );
    }
}

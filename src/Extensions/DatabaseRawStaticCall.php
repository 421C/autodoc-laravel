<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class DatabaseRawStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->methodName !== 'raw') {
            return null;
        }

        if (! $call->className || ! is_a($call->className, DB::class, true)) {
            return null;
        }

        return new ObjectType(
            className: Expression::class,
            constructorArgs: $call->argTypes,
        );
    }
}

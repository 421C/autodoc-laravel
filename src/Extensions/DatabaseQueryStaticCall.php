<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\QueryBuilder\BuilderType;
use AutoDoc\Laravel\QueryBuilder\QueryChain;
use AutoDoc\Laravel\QueryBuilder\QueryChainMethod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DatabaseQueryStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->methodName !== 'table' && $call->methodName !== 'query') {
            return null;
        }

        if (! $call->className || ! is_a($call->className, DB::class, true)) {
            return null;
        }

        return new BuilderType(
            chain: new QueryChain(
                modelClassName: null,
                methods: [new QueryChainMethod($call->methodName, $call->argTypes)],
                isRawDatabaseQuery: true,
            ),
            builderClassName: Builder::class,
        );
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\FuncCallContext;
use AutoDoc\Extensions\FuncCallExtension;
use AutoDoc\Laravel\Helpers\ReadsDataPath;

/**
 * Resolves the `data_get()` helper by reading its dot path through the target's array types.
 */
class DataGetCall extends FuncCallExtension
{
    use ReadsDataPath;

    public function getReturnType(FuncCallContext $call): ?Type
    {
        if ($call->functionName !== 'data_get') {
            return null;
        }

        return $this->readDataPath(
            target: $this->argument($call, 'target', 0) ?? new UnknownType,
            key: $this->argument($call, 'key', 1),
            default: $this->defaultValue($call, 2),
            config: $call->scope->config,
        );
    }
}

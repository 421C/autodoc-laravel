<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ClassStringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnresolvedClassType;
use AutoDoc\Extensions\FuncCallContext;
use AutoDoc\Extensions\FuncCallExtension;
use Illuminate\Foundation\Application;

/**
 * Handles `app(...)`.
 */
class AppCall extends FuncCallExtension
{
    public function getReturnType(FuncCallContext $call): ?Type
    {
        if ($call->functionName !== 'app') {
            return null;
        }

        $abstractIndex = $call->argTypes->indexForParameter('abstract', 0);

        if ($abstractIndex === null) {
            return new UnresolvedClassType(className: Application::class, scope: $call->scope);
        }

        $abstractType = $call->argTypes->get($abstractIndex);

        if ($abstractType instanceof ClassStringType && $abstractType->className) {
            return new UnresolvedClassType(className: $abstractType->className, scope: $call->scope);
        }

        return null;
    }
}

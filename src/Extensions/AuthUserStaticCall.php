<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\Helpers\AuthGuardSelection;
use AutoDoc\Laravel\Helpers\AuthUserTypeResolver;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves `Auth::user()` and `Auth::id()` to the configured user model.
 */
class AuthUserStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCallContext $call): ?Type
    {
        $methodName = $call->methodName;

        if ($methodName !== 'user' && $methodName !== 'id') {
            return null;
        }

        if ($call->className === null || ! is_a($call->className, Auth::class, true)) {
            return null;
        }

        $resolver = new AuthUserTypeResolver;

        return $methodName === 'user'
            ? $resolver->resolveUserType($call->scope, AuthGuardSelection::implicit())
            : $resolver->resolveUserIdType($call->scope, AuthGuardSelection::implicit());
    }
}

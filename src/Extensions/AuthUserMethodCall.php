<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\AuthGuardSelection;
use AutoDoc\Laravel\Helpers\AuthUserTypeResolver;
use AutoDoc\Laravel\Helpers\ChecksRequestReceiver;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;

/**
 * Resolves `user()` and `id()` on the `auth()` helper, the `Auth` facade guard,
 * a resolved auth factory, or the Laravel Request to the configured user model.
 */
class AuthUserMethodCall extends MethodCallExtension
{
    use ChecksRequestReceiver;

    /** @var list<class-string> */
    private const AUTH_RECEIVER_CLASSES = [
        Guard::class,
        AuthFactory::class,
        AuthManager::class,
    ];


    public function getReturnType(MethodCallContext $call): ?Type
    {
        $methodName = $call->methodName;

        if ($methodName !== 'user' && $methodName !== 'id') {
            return null;
        }

        if (! $this->isAuthReceiver($call, $methodName)) {
            return null;
        }

        $guardSelection = $this->resolveGuardSelection($call);

        if (! $guardSelection->isKnown()) {
            return null;
        }

        $resolver = new AuthUserTypeResolver;

        return $methodName === 'user'
            ? $resolver->resolveUserType($call->scope, $guardSelection)
            : $resolver->resolveUserIdType($call->scope, $guardSelection);
    }


    private function isAuthReceiver(MethodCallContext $call, string $methodName): bool
    {
        $var = $call->node->var;

        if ($this->isAuthHelperCall($var) || $this->isAuthFacadeStaticCall($var, $call->scope)) {
            return true;
        }

        // The Request exposes `user()` but not `id()`.
        if ($methodName === 'user' && $this->isRequestReceiver($call)) {
            return true;
        }

        $varType = $call->getVarType()->unwrapType($call->scope->config);

        if ($varType instanceof ObjectType && $varType->className) {
            foreach (self::AUTH_RECEIVER_CLASSES as $authClass) {
                if (is_a($varType->className, $authClass, true)) {
                    return true;
                }
            }
        }

        return false;
    }


    private function resolveGuardSelection(MethodCallContext $call): AuthGuardSelection
    {
        $var = $call->node->var;
        $scope = $call->scope;

        if ($this->isAuthHelperCall($var)) {
            return $this->guardSelectionFromArgument($var->args[0] ?? null, $scope);
        }

        if (($var instanceof StaticCall || $var instanceof MethodCall)
            && $var->name instanceof Node\Identifier
            && $var->name->toString() === 'guard'
        ) {
            return $this->guardSelectionFromArgument($var->args[0] ?? null, $scope);
        }

        if ($call->methodName === 'user' && $this->isRequestReceiver($call)) {
            return $this->guardSelectionFromArgument($call->node->args[0] ?? null, $scope);
        }

        $varType = $call->getVarType()->unwrapType($scope->config);

        if ($varType instanceof ObjectType && $varType->className !== null
            && ! is_a($varType->className, Guard::class, true)
            && (is_a($varType->className, AuthFactory::class, true)
                || is_a($varType->className, AuthManager::class, true))
        ) {
            return AuthGuardSelection::implicit();
        }

        return AuthGuardSelection::unknown();
    }


    /**
     * @phpstan-assert-if-true FuncCall $node
     */
    private function isAuthHelperCall(Node $node): bool
    {
        return $node instanceof FuncCall
            && $node->name instanceof Node\Name
            && $node->name->toString() === 'auth';
    }


    private function isAuthFacadeStaticCall(Node $node, Scope $scope): bool
    {
        if (! ($node instanceof StaticCall) || ! ($node->class instanceof Node\Name)) {
            return false;
        }

        $className = $scope->getResolvedClassName($node->class);

        return $className !== null && is_a($className, Auth::class, true);
    }


    private function guardSelectionFromArgument(Node\Arg|Node\VariadicPlaceholder|null $arg, Scope $scope): AuthGuardSelection
    {
        if ($arg === null) {
            return AuthGuardSelection::implicit();
        }

        if (! $arg instanceof Arg || $arg->unpack) {
            return AuthGuardSelection::unknown();
        }

        return $this->guardSelectionFromType(
            $scope->resolveType($arg->value)->unwrapType($scope->config),
            $scope,
        );
    }


    private function guardSelectionFromType(Type $type, Scope $scope): AuthGuardSelection
    {
        $type = $type->unwrapType($scope->config);

        if ($type instanceof NullType) {
            return AuthGuardSelection::implicit();
        }

        if ($type instanceof StringType) {
            $possibleGuardNames = $type->getPossibleValues();

            if ($possibleGuardNames === null) {
                return AuthGuardSelection::unknown();
            }

            $guardNames = array_values(array_filter(
                $possibleGuardNames,
                fn (string $guardName): bool => (bool) $guardName,
            ));

            if ($guardNames === []) {
                return AuthGuardSelection::implicit();
            }

            if (count($guardNames) !== count($possibleGuardNames)) {
                return AuthGuardSelection::unknown();
            }

            return AuthGuardSelection::explicit($guardNames);
        }

        if ($type instanceof UnionType) {
            $guardNames = [];

            foreach ($type->types as $variantType) {
                $selection = $this->guardSelectionFromType($variantType, $scope);

                if (! $selection->isKnown()) {
                    return AuthGuardSelection::unknown();
                }

                if ($selection->isImplicit()) {
                    return count($type->types) === 1
                        ? AuthGuardSelection::implicit()
                        : AuthGuardSelection::unknown();
                }

                $selectionGuardNames = $selection->guardNames;

                if ($selectionGuardNames === null) {
                    return AuthGuardSelection::unknown();
                }

                array_push($guardNames, ...$selectionGuardNames);
            }

            return AuthGuardSelection::explicit($guardNames);
        }

        return AuthGuardSelection::unknown();
    }
}

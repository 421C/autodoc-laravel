<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\MethodCallContext;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

/**
 * Handles Laravel Request `query` method.
 */
class RequestQuery extends MethodCallExtension
{
    public function handleSideEffect(MethodCallContext $call): void
    {
        if (! $call->scope->route || ! $this->isRequestQueryMethod($call)) {
            return;
        }

        $keyArgType = $call->argTypes->has(0) ? $call->argTypes->get(0) : null;

        if ($keyArgType instanceof StringType) {
            foreach ($keyArgType->getPossibleValues() ?? [] as $key) {
                $call->scope->route->requestQueryParams[$key] ??= new UnknownType;
            }
        }
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! $this->isRequestQueryMethod($call)) {
            return null;
        }

        $keyArgType = $call->argTypes->has(0) ? $call->argTypes->get(0) : null;

        if ($keyArgType instanceof StringType) {
            return new UnionType([
                new StringType,
                new NullType,
                new ArrayType(itemType: new UnknownType),
            ]);

        } else if ($keyArgType === null || $keyArgType instanceof NullType) {
            return new ArrayType(
                keyType: new StringType,
                itemType: new UnionType([
                    new StringType,
                    new ArrayType(itemType: new UnknownType),
                ]),
            );
        }

        return new ArrayType(
            itemType: new UnionType([
                new StringType,
                new NullType,
                new ArrayType(itemType: new UnknownType),
            ]),
        );
    }


    private function isRequestQueryMethod(MethodCallContext $call): bool
    {
        if ($call->methodName !== 'query') {
            return false;
        }

        $var = $call->node->var;

        if ($var instanceof FuncCall
            && $var->name instanceof Node\Name
            && $var->name->name === 'request'
        ) {
            return true;
        }

        $varType = $call->getVarType();

        if ($varType instanceof ObjectType && $varType->className) {
            return is_a($varType->className, Request::class, true);
        }

        return false;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\Extensions\MethodCallContext;
use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

trait ChecksRequestReceiver
{
    /**
     * Whether the call subject is the `request()` helper or a variable of type `Illuminate\Http\Request`.
     */
    protected function isRequestReceiver(MethodCallContext $call): bool
    {
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

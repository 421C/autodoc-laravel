<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodClassifier;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodResolver;
use AutoDoc\Laravel\QueryBuilder\BuilderType;
use AutoDoc\Laravel\QueryBuilder\QueryChainMethod;
use Illuminate\Database\Eloquent\Builder;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;

class EloquentBuilderMethodCall extends MethodCallExtension
{
    public function handleSideEffect(MethodCallContext $call): void
    {
        $node = $call->node;

        if (! ($node instanceof MethodCall)
            || ! ($node->var instanceof Variable)
            || ! is_string($node->var->name)
        ) {
            return;
        }

        $continuedType = $this->continueChain($call);

        if ($continuedType) {
            $call->setVarType($node->var->name, $continuedType);
        }
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        $continuedType = $this->continueChain($call);

        if ($continuedType) {
            return $continuedType;
        }

        $varType = $call->getVarType();

        if (! ($varType instanceof ObjectType) || $varType->className !== Builder::class) {
            return null;
        }

        return (new BuilderMethodResolver($call->scope))->getReturnType($call->methodName, $call->argTypes);
    }


    private function continueChain(MethodCallContext $call): ?BuilderType
    {
        if (BuilderMethodClassifier::terminatesBuilderChain($call->methodName)) {
            return null;
        }

        $varType = $call->getVarType();

        if (! ($varType instanceof BuilderType)) {
            return null;
        }

        return new BuilderType(
            chain: $varType->chain->withMethod(new QueryChainMethod($call->methodName, $call->argTypes)),
            builderClassName: $varType->className ?? Builder::class,
        );
    }
}

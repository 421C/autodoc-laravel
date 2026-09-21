<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodClassifier;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodResolver;
use AutoDoc\Laravel\QueryBuilder\BuilderState;
use AutoDoc\Laravel\QueryBuilder\ConditionalCallback;
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


    private function continueChain(MethodCallContext $call): ?Type
    {
        if (BuilderMethodClassifier::terminatesBuilderChain($call->methodName)) {
            return null;
        }

        $state = BuilderState::in($call->getVarType());

        if (! $state) {
            return null;
        }

        return $this->continued($state, $call)->toType($call->scope->config);
    }


    private function continued(BuilderState $state, MethodCallContext $call): BuilderState
    {
        $method = new QueryChainMethod($call->methodName, $call->argTypes);
        $node = $call->node;

        $conditional = $node instanceof MethodCall
            ? ConditionalCallback::read($method, $node, $state->chain(), $call->scope)
            : null;

        if ($conditional) {
            return $state->applying($conditional->withoutRowPreservingCalls($state->modelClassName()));
        }

        $belongsInChain = $state->nextMethodIsConditional()
            || BuilderMethodClassifier::belongsInChain($method->name, $state->modelClassName());

        return $belongsInChain ? $state->withMethod($method) : $state;
    }
}

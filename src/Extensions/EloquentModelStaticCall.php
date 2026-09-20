<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodClassifier;
use AutoDoc\Laravel\QueryBuilder\BuilderType;
use AutoDoc\Laravel\QueryBuilder\QueryChain;
use AutoDoc\Laravel\QueryBuilder\QueryChainMethod;
use AutoDoc\Laravel\QueryBuilder\QueryNavigator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles static calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelStaticCall extends StaticCallExtension
{
    public function handleSideEffect(StaticCallContext $call): void
    {
        if (! BuilderMethodClassifier::throwsModelNotFound($call->methodName)) {
            return;
        }

        $className = $call->className;

        if (! $className || ! is_subclass_of($className, Model::class)) {
            return;
        }

        (new QueryNavigator($call->scope))->recordFailingFinisherResponse($call->node);
    }


    public function getReturnType(StaticCallContext $call): ?Type
    {
        $methodName = $call->methodName;

        if ($methodName === 'toArray' || $methodName === 'attributesToArray') {
            return $this->getModelAttributesShape($call);
        }

        if (! BuilderMethodClassifier::supportsModelStaticCall($methodName)) {
            return $this->getBuilderType($call);
        }

        $className = $call->className;

        if (! $className) {
            return null;
        }

        if (! is_subclass_of($className, Model::class)) {
            return null;
        }

        return (new QueryNavigator($call->scope))->getResultType($call->node, $methodName);
    }


    private function getBuilderType(StaticCallContext $call): ?BuilderType
    {
        $className = $call->className;

        if (! $className || ! is_subclass_of($className, Model::class)) {
            return null;
        }

        if (! BuilderMethodClassifier::startsBuilderChain($call->methodName, $className)) {
            return null;
        }

        return new BuilderType(
            chain: new QueryChain(
                modelClassName: $className,
                methods: [new QueryChainMethod($call->methodName, $call->argTypes)],
            ),
            builderClassName: Builder::class,
        );
    }


    /**
     * Resolves `parent::toArray()` / `parent::attributesToArray()` inside a
     * custom `toArray()` body. The call runs with the child's `$this`, so the
     * analyzed class supplies the attribute shape.
     */
    private function getModelAttributesShape(StaticCallContext $call): ?ArrayType
    {
        $className = $call->className;

        if (! $className || ! is_a($className, Model::class, true)) {
            return null;
        }

        $scopeClassName = $call->scope->className;

        $modelClassName = $scopeClassName && is_subclass_of($scopeClassName, Model::class)
            ? $scopeClassName
            : $className;

        return (new EloquentModel)->getModelAttributesArrayType($call->scope, $modelClassName);
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\Extensions\MethodCallContext;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;

trait MutatesModelReceiver
{
    use ResolvesModelTypes;

    /**
     * @param array<string, Type> $attributes
     */
    protected function mutateModelReceiver(MethodCallContext $call, ObjectType $modelType, array $attributes): void
    {
        if ($attributes === []) {
            return;
        }

        if ($this->mutateNullablePropertyReceiver($call, $modelType, $attributes)) {
            return;
        }

        if ($call->node instanceof NullsafeMethodCall
            && $this->typeIncludesNull($call->getVarType())
        ) {
            return;
        }

        $call->mutateExpression($call->node->var, $this->copyAttributes($attributes));
    }


    /**
     * @param array<string, Type> $attributes
     */
    private function mutateNullablePropertyReceiver(
        MethodCallContext $call,
        ObjectType $modelType,
        array $attributes,
    ): bool {
        if (! ($call->node instanceof NullsafeMethodCall)
            || ! $this->typeIncludesNull($call->getVarType())
            || ! ($call->node->var instanceof PropertyFetch
                || $call->node->var instanceof NullsafePropertyFetch)
        ) {
            return false;
        }

        $propertyName = $call->scope->getRawValueFromNode($call->node->var->name);

        if (! is_string($propertyName)) {
            return false;
        }

        $mutatedModelType = clone $modelType;
        $mutatedModelType->properties = array_merge($mutatedModelType->properties, $this->copyAttributes($attributes));

        $receiverType = $call->getVarType();
        $receiverVariants = $receiverType instanceof UnionType ? $receiverType->types : [$receiverType];

        $updatedReceiverVariants = array_map(
            fn (Type $variant): Type => $variant === $modelType ? $mutatedModelType : $variant,
            $receiverVariants,
        );

        $updatedReceiverType = new UnionType($updatedReceiverVariants)
            ->unwrapType($call->scope->config)
            ->setRequired($receiverType->required);

        $call->mutateExpression($call->node->var->var, [
            $propertyName => $updatedReceiverType,
        ]);

        return true;
    }


    /**
     * @param array<string, Type> $attributes
     * @return array<string, Type>
     */
    private function copyAttributes(array $attributes): array
    {
        return array_map(fn (Type $type): Type => clone $type, $attributes);
    }
}

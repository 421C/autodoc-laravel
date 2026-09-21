<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Laravel\Extensions\EloquentModel;
use Illuminate\Database\Eloquent\Model;

final class ModelVisibility
{
    use InspectsModelAttributes, ParsesKeyListArguments;

    public const METHODS = [
        'makeHidden',
        'makeVisible',
        'setHidden',
        'setVisible',
        'append',
        'setAppends',
    ];

    private const REPLACING_METHODS = [
        'setHidden',
        'setVisible',
        'setAppends',
    ];

    /**
     * @param array<string, Type> $properties
     * @param array<string, Type> $hiddenProperties
     * @param class-string<Model> $modelClassName
     */
    private function __construct(
        private readonly MethodCallContext $call,
        private readonly Model $model,
        private readonly string $modelClassName,
        private array $properties,
        private array $hiddenProperties,
    ) {}


    public static function apply(MethodCallContext $call, ObjectType $modelType): ?ObjectType
    {
        $modelClassName = $modelType->className;

        if ($modelClassName === null || ! is_subclass_of($modelClassName, Model::class)) {
            return null;
        }

        $model = ModelResolver::resolve($modelClassName);

        if ($model === null) {
            return null;
        }

        return new ModelVisibility(
            call: $call,
            model: $model,
            modelClassName: $modelClassName,
            properties: $modelType->properties,
            hiddenProperties: $modelType->hiddenProperties,
        )->repartitionedType($modelType);
    }


    private function repartitionedType(ObjectType $modelType): ?ObjectType
    {
        $methodName = $this->call->methodName;
        $isReplacing = in_array($methodName, self::REPLACING_METHODS, true);

        $keyNames = $this->resolveKeyListNames($this->call->argTypes, $this->call->scope->config, allowVariadic: ! $isReplacing);

        if ($keyNames === [] && ! ($isReplacing && $this->hasEmptyKeyListArgument($this->call->argTypes, $this->call->scope->config))) {
            return null;
        }

        match ($methodName) {
            'makeHidden' => $this->moveToHidden($keyNames),
            'makeVisible' => $this->moveToVisible($keyNames),
            'setHidden' => $this->repartition(visible: $this->model->getVisible(), hidden: $keyNames),
            'setVisible' => $this->repartition(visible: $keyNames, hidden: $this->model->getHidden()),
            'append' => $this->append($keyNames),
            default => $this->setAppends($keyNames),
        };

        $repartitionedType = clone $modelType;

        $repartitionedType->properties = $this->properties;
        $repartitionedType->hiddenProperties = $this->hiddenProperties;

        return $repartitionedType;
    }


    /**
     * @param list<string> $keyNames
     */
    private function moveToHidden(array $keyNames): void
    {
        foreach ($keyNames as $keyName) {
            if (isset($this->properties[$keyName])) {
                $this->hiddenProperties[$keyName] = clone $this->properties[$keyName];

                unset($this->properties[$keyName]);
            }
        }
    }


    /**
     * @param list<string> $keyNames
     */
    private function moveToVisible(array $keyNames): void
    {
        foreach ($keyNames as $keyName) {
            if (isset($this->hiddenProperties[$keyName])) {
                $this->properties[$keyName] = $this->serializedAttributeType($this->hiddenProperties[$keyName]);

                unset($this->hiddenProperties[$keyName]);
            }
        }
    }


    /**
     * A hidden attribute carries no `required` flag, since only the visible
     * side is ever serialized. Laravel always emits a visible attribute's key.
     */
    private function serializedAttributeType(Type $attributeType): Type
    {
        return (clone $attributeType)->setRequired(true);
    }


    /**
     * Mirrors `getArrayableItems()`: a non-empty visible list is a whitelist,
     * and the hidden list is subtracted from whatever survives it.
     *
     * @param array<string> $visible
     * @param array<string> $hidden
     */
    private function repartition(array $visible, array $hidden): void
    {
        $attributes = array_merge($this->properties, $this->hiddenProperties);

        $this->properties = [];
        $this->hiddenProperties = [];

        foreach ($attributes as $keyName => $attributeType) {
            $isVisible = ($visible === [] || in_array($keyName, $visible, true))
                && ! in_array($keyName, $hidden, true);

            if ($isVisible) {
                $this->properties[$keyName] = $this->serializedAttributeType($attributeType);

            } else {
                $this->hiddenProperties[$keyName] = clone $attributeType;
            }
        }
    }


    /**
     * @param list<string> $keyNames
     */
    private function append(array $keyNames): void
    {
        foreach ($keyNames as $keyName) {
            if (isset($this->properties[$keyName]) || isset($this->hiddenProperties[$keyName])) {
                continue;
            }

            $attributeType = $this->resolveAppendedAttributeType($keyName);

            if ($attributeType === null) {
                continue;
            }

            if ($this->isModelAttributeHidden($this->model, $keyName)) {
                $this->hiddenProperties[$keyName] = $attributeType;

            } else {
                $this->properties[$keyName] = $attributeType;
            }
        }
    }


    /**
     * @param list<string> $keyNames
     */
    private function setAppends(array $keyNames): void
    {
        foreach ($this->model->getAppends() as $appendedKeyName) {
            if (! in_array($appendedKeyName, $keyNames, true)) {
                unset($this->properties[$appendedKeyName], $this->hiddenProperties[$appendedKeyName]);
            }
        }

        $this->append($keyNames);
    }


    private function resolveAppendedAttributeType(string $keyName): ?Type
    {
        $phpClass = $this->call->scope->getPhpClassInDeeperScope($this->modelClassName);

        return new EloquentModel()->getPropertyType($phpClass, $keyName)?->setRequired(true);
    }
}

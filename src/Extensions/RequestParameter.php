<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpEnum;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\FloatType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\ChecksRequestReceiver;
use AutoDoc\Laravel\Helpers\DotNotationParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

/**
 * Handles Laravel Request parameter methods.
 */
class RequestParameter extends MethodCallExtension
{
    use ChecksRequestReceiver, DotNotationParser;

    private const METHODS = [
        'boolean',
        'string',
        'float',
        'enum',
        'enums',
        'array',
        'collect',
        'date',
        'input',
        'integer',
        'str',
    ];

    /**
     * Only array/collect key-list calls need a Laravel-specific return shape.
     */
    public function getReturnType(MethodCallContext $call): ?Type
    {
        $methodName = $this->getMatchedMethodName($call);

        if (in_array($methodName, ['array', 'collect'], true)) {
            return $this->getArrayOrCollectReturnType($methodName, $call);
        }

        return null;
    }


    public function handleSideEffect(MethodCallContext $call): void
    {
        if (! $call->scope->route) {
            return;
        }

        $methodName = $this->getMatchedMethodName($call);

        if ($methodName === null) {
            return;
        }

        $requestParameters = $this->getRequestParametersFromMethod($methodName, $call);

        if ($requestParameters === []) {
            return;
        }

        if ($call->scope->route->hasMethod('get')) {
            foreach ($requestParameters as $key => $paramType) {
                $call->scope->route->requestQueryParams[$key] ??= $paramType;
            }

            return;
        }

        $paramsInRequestBody = [];

        foreach ($requestParameters as $key => $paramType) {
            if (! array_key_exists($key, $call->scope->route->requestQueryParams)) {
                $paramsInRequestBody[$key] = $paramType;
            }
        }

        if (! $paramsInRequestBody) {
            return;
        }

        $call->setRequestType(new ObjectType(properties: $paramsInRequestBody));
    }


    /**
     * Request helper methods imply parameters from their key argument.
     *
     * @return array<string, Type>
     */
    private function getRequestParametersFromMethod(string $methodName, MethodCallContext $call): array
    {
        if (in_array($methodName, ['array', 'collect'], true)) {
            $selectedFields = $this->getFieldsSelectedByArrayOrCollect($call);

            if ($selectedFields !== []) {
                return $selectedFields;
            }
        }

        $fieldNameType = $call->argTypes->has(0)
            ? $call->argTypes->get(0)->unwrapType($call->scope->config)
            : null;

        if (! ($fieldNameType instanceof StringType)) {
            return [];
        }

        $fieldNames = $fieldNameType->getPossibleValues();

        if (! $fieldNames) {
            return [];
        }

        $parameterType = $this->resolveParamType($methodName, $call);
        $parameters = [];

        foreach ($fieldNames as $fieldName) {
            $this->dotNotationToNestedArrayType($parameters, $this->splitDotNotation($fieldName), clone $parameterType);
        }

        return $parameters;
    }


    /**
     * The key-list overload returns an input subset, not one field's array value.
     */
    private function getArrayOrCollectReturnType(string $methodName, MethodCallContext $call): ?Type
    {
        $selectedFields = $this->getFieldsSelectedByArrayOrCollect($call);

        if ($selectedFields === []) {
            return null;
        }

        return new ArrayType(
            shape: $selectedFields,
            className: $methodName === 'collect' ? Collection::class : null,
        );
    }


    /**
     * Literal key lists let array/collect expose the shape of only($keys).
     *
     * @return array<string, Type>
     */
    private function getFieldsSelectedByArrayOrCollect(MethodCallContext $call): array
    {
        $keyListType = $call->argTypes->has(0)
            ? $call->argTypes->get(0)->unwrapType($call->scope->config)
            : null;

        if (! ($keyListType instanceof ArrayType)) {
            return [];
        }

        $fieldNames = [];

        foreach ($keyListType->shape as $fieldNameType) {
            $this->appendStringValuesFromType($fieldNameType, $call, $fieldNames);
        }

        if ($keyListType->itemType) {
            $this->appendStringValuesFromType($keyListType->itemType, $call, $fieldNames);
        }

        $fields = [];

        foreach (array_unique($fieldNames) as $fieldName) {
            $this->dotNotationToNestedArrayType($fields, $this->splitDotNotation($fieldName), new UnknownType);
        }

        return $fields;
    }


    /**
     * Unions can preserve literal field names from variable key lists.
     *
     * @param list<string> $fieldNames
     */
    private function appendStringValuesFromType(Type $type, MethodCallContext $call, array &$fieldNames): void
    {
        $type = $type->unwrapType($call->scope->config);

        if ($type instanceof StringType) {
            array_push($fieldNames, ...($type->getPossibleValues() ?? []));

            return;
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $typeInUnion) {
                $this->appendStringValuesFromType($typeInUnion, $call, $fieldNames);
            }
        }
    }


    private function resolveParamType(string $methodName, MethodCallContext $call): Type
    {
        return match ($methodName) {
            'boolean' => new BoolType,
            'string', 'str' => new ObjectType(className: Stringable::class, typeToDisplay: new StringType),
            'float' => new FloatType,
            'integer' => new IntegerType,
            'date' => new StringType(format: 'date'),
            'array' => new ArrayType(itemType: new UnknownType),
            'collect' => new ArrayType(itemType: new UnknownType, className: Collection::class),
            'enum' => $this->resolveEnumParamType($call),
            'enums' => new ArrayType(itemType: $this->resolveEnumParamType($call)),
            default => new UnknownType,
        };
    }


    private function resolveEnumParamType(MethodCallContext $call): Type
    {
        $enumArgType = $call->argTypes->has(1) ? $call->argTypes->get(1) : null;

        if ($enumArgType instanceof StringType
            && is_string($enumArgType->value)
            && enum_exists($enumArgType->value)
        ) {
            $enumClass = $call->scope->getPhpClassInDeeperScope($enumArgType->value);

            return (new PhpEnum($enumClass))->resolveType();
        }

        return new UnknownType;
    }


    private function getMatchedMethodName(MethodCallContext $call): ?string
    {
        if (! in_array($call->methodName, self::METHODS, true)) {
            return null;
        }

        return $this->isRequestReceiver($call) ? $call->methodName : null;
    }
}

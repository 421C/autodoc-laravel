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
        'only',
        'except',
        'all',
    ];

    private const KEY_LIST_METHODS = [
        'array',
        'collect',
        'only',
        'except',
        'all',
    ];

    private const SHAPED_KEY_LIST_METHODS = [
        'array',
        'collect',
        'only',
        'all',
    ];

    private const VARIADIC_KEY_LIST_METHODS = [
        'only',
        'except',
        'all',
    ];


    public function getReturnType(MethodCallContext $call): ?Type
    {
        $methodName = $this->getMatchedMethodName($call);

        if ($methodName === null) {
            return null;
        }

        if ($methodName === 'all' && count($call->argTypes) === 0) {
            return $this->getAllReturnType($call);
        }

        if (in_array($methodName, self::SHAPED_KEY_LIST_METHODS, true)) {
            return $this->getKeyListReturnType($methodName, $call);
        }

        return null;
    }


    public function handleSideEffect(MethodCallContext $call): void
    {
        $route = $call->scope->route;

        if (! $route) {
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

        if ($route->hasMethod('get')) {
            foreach ($requestParameters as $key => $paramType) {
                $route->requestQueryParams[$key] ??= $paramType;
            }

            return;
        }

        $paramsInRequestBody = [];

        foreach ($requestParameters as $key => $paramType) {
            if (! array_key_exists($key, $route->requestQueryParams)) {
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
        if (in_array($methodName, self::KEY_LIST_METHODS, true)) {
            $selectedFields = $this->getKeyListFields($methodName, $call);

            if ($selectedFields !== []) {
                return $selectedFields;
            }

            if (in_array($methodName, self::VARIADIC_KEY_LIST_METHODS, true)) {
                return [];
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


    private function getKeyListReturnType(string $methodName, MethodCallContext $call): ?Type
    {
        $selectedFields = $this->getKeyListFields($methodName, $call);

        if ($selectedFields === []) {
            return null;
        }

        return new ArrayType(
            shape: $selectedFields,
            className: $methodName === 'collect' ? Collection::class : null,
        );
    }


    private function getAllReturnType(MethodCallContext $call): Type
    {
        $route = $call->scope->route;

        if (! $route) {
            return new ArrayType(itemType: new UnknownType);
        }

        $knownFields = $route->requestQueryParams;
        $requestBody = $route->getRequestBodyType($call->scope);

        if ($requestBody instanceof ObjectType) {
            $knownFields = array_replace($knownFields, $requestBody->properties);

        } else if ($requestBody instanceof ArrayType) {
            $knownFields = array_replace($knownFields, $requestBody->shape);
        }

        if ($knownFields !== []) {
            return new ArrayType(shape: $knownFields);
        }

        return new ArrayType(itemType: new UnknownType);
    }


    /**
     * `only`/`except`/`all` accept either a single array of keys or variadic
     * string keys; `array`/`collect` only take the array form.
     *
     * @return array<string, Type>
     */
    private function getKeyListFields(string $methodName, MethodCallContext $call): array
    {
        if (! $call->argTypes->has(0)) {
            return [];
        }

        $firstArgType = $call->argTypes->get(0)->unwrapType($call->scope->config);

        if ($firstArgType instanceof ArrayType) {
            return $this->getFieldsFromKeyListType($firstArgType, $call);
        }

        if (! in_array($methodName, self::VARIADIC_KEY_LIST_METHODS, true)) {
            return [];
        }

        $fieldNames = [];

        for ($index = 0; $index < count($call->argTypes); $index++) {
            $this->appendStringValuesFromType($call->argTypes->get($index), $call, $fieldNames);
        }

        return $this->getFieldsFromNames($fieldNames);
    }


    /** @return array<string, Type> */
    private function getFieldsFromKeyListType(ArrayType $keyListType, MethodCallContext $call): array
    {
        $fieldNames = [];

        foreach ($keyListType->shape as $fieldNameType) {
            $this->appendStringValuesFromType($fieldNameType, $call, $fieldNames);
        }

        if ($keyListType->itemType) {
            $this->appendStringValuesFromType($keyListType->itemType, $call, $fieldNames);
        }

        return $this->getFieldsFromNames($fieldNames);
    }


    /**
     * @param list<string> $fieldNames
     * @return array<string, Type>
     */
    private function getFieldsFromNames(array $fieldNames): array
    {
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

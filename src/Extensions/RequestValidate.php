<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\ChecksRequestReceiver;
use AutoDoc\Laravel\Validation\ValidationRulesParser;

/**
 * Handles Laravel Request `validate` method.
 */
class RequestValidate extends MethodCallExtension
{
    use ChecksRequestReceiver, ValidationRulesParser;

    public function handleSideEffect(MethodCallContext $call): void
    {
        if ($this->isRequestValidateMethod($call)) {
            $requestType = $this->parseValidateMethodCallArguments($call);

            if ($requestType !== null) {
                $call->setRequestType($requestType);
            }
        }
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        if ($this->isRequestValidateMethod($call)) {
            return $this->parseValidateMethodCallArguments($call);
        }

        return null;
    }


    private function isRequestValidateMethod(MethodCallContext $call): bool
    {
        return $call->methodName === 'validate' && $this->isRequestReceiver($call);
    }


    private function parseValidateMethodCallArguments(MethodCallContext $call): ?ArrayType
    {
        if (! $call->argTypes->has(0)) {
            return null;
        }

        $scope = $call->scope;
        $validationArray = $call->argTypes->get(0);

        if (! isset($validationArray->shape)) {
            return null;
        }

        $requestDataObjectType = $scope->withoutScalarTypeValueMerging(function () use ($scope, $validationArray) {
            return $this->parseValidationRules($validationArray->shape, $scope);
        });

        if ($requestDataObjectType instanceof ObjectType) {
            return new ArrayType(shape: $requestDataObjectType->properties);
        }

        return $requestDataObjectType;
    }
}

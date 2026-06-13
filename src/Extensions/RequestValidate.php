<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\MethodCallContext;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Validation\ValidationRulesParser;
use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

/**
 * Handles Laravel Request `validate` method.
 */
class RequestValidate extends MethodCallExtension
{
    use ValidationRulesParser;

    public function getRequestType(MethodCallContext $call): ?Type
    {
        if ($this->isRequestValidateMethod($call)) {
            return $this->parseValidateMethodCallArguments($call);
        }

        return null;
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
        if ($call->methodName !== 'validate') {
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

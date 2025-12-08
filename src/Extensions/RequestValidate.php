<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnresolvedParserNodeType;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Validation\ValidationRulesParser;
use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;

/**
 * Handles Laravel Request `validate` method.
 */
class RequestValidate extends MethodCallExtension
{
    use ValidationRulesParser;

    public function getRequestType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if ($this->isRequestValidateMethod($methodCall, $scope)) {
            return $this->parseValidateMethodCallArguments($methodCall, $scope);
        }

        return null;
    }


    public function getReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if ($this->isRequestValidateMethod($methodCall, $scope)) {
            return $this->parseValidateMethodCallArguments($methodCall, $scope);
        }

        return null;
    }


    private function isRequestValidateMethod(MethodCall $methodCall, Scope $scope): bool
    {
        if (! ($methodCall->name instanceof Node\Identifier)
            || $methodCall->name->name !== 'validate'
        ) {
            return false;
        }

        if ($methodCall->var instanceof Variable) {
            $unresolvedVarType = $scope->getVariableType($methodCall->var);

            if ($unresolvedVarType instanceof UnresolvedParserNodeType
                && $unresolvedVarType->node instanceof Node\Name
            ) {
                $className = $scope->getResolvedClassName($unresolvedVarType->node);

                if (! $className) {
                    return false;
                }

                if (is_a($className, Request::class, true)) {
                    return true;
                }
            }
        }

        if ($methodCall->var instanceof FuncCall
            && $methodCall->var->name instanceof Node\Name
            && $methodCall->var->name->name === 'request'
        ) {
            return true;
        }

        return false;
    }


    private function parseValidateMethodCallArguments(MethodCall $methodCall, Scope $scope): ?ArrayType
    {
        $validationArrayNode = $methodCall->getArgs()[0]->value;
        $validationArray = $scope->resolveType($validationArrayNode);

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

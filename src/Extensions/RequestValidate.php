<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnresolvedParserNodeType;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Validation\ValidationRulesParser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        if (! ($methodCall->name instanceof Node\Identifier)
            || $methodCall->name->name !== 'validate'
        ) {
            return null;
        }

        if ($methodCall->var instanceof Variable) {
            $unresolvedVarType = $scope->getVariableType($methodCall->var);

            if ($unresolvedVarType instanceof UnresolvedParserNodeType
                && $unresolvedVarType->node instanceof Node\Name
            ) {
                $className = $scope->getResolvedClassName($unresolvedVarType->node);

                if (! $className) {
                    return null;
                }

                if (is_a($className, Request::class, true)) {
                    return $this->parseValidateMethodCallArguments($methodCall, $scope);
                }
            }
        }

        if ($methodCall->var instanceof FuncCall
            && $methodCall->var->name instanceof Node\Name
            && $methodCall->var->name->name === 'request'
        ) {
            return $this->parseValidateMethodCallArguments($methodCall, $scope);
        }

        return null;
    }


    private function parseValidateMethodCallArguments(MethodCall $methodCall, Scope $scope): ?Type
    {
        $validationArrayNode = $methodCall->getArgs()[0]->value;
        $validationArray = $scope->resolveType($validationArrayNode);

        if (! isset($validationArray->shape)) {
            return null;
        }

        $validatedStructure = Arr::undot($validationArray->shape);

        return $this->parseValidatedStructure($validatedStructure);
    }
}

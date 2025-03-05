<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpFunctionArgument;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallExtension;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;

/**
 * Handles static calls on `Illuminate\Validation\Rule` class.
 */
class ValidationRuleStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCall $methodCall, Scope $scope): ?Type
    {
        if ($methodCall->name instanceof Node\Identifier
            && $methodCall->class instanceof Node\Name
            && $scope->getResolvedClassName($methodCall->class) === Rule::class
        ) {
            $methods = [
                'enum' => fn () => new ObjectType(
                    className: Enum::class,
                    constructorArgs: PhpFunctionArgument::list($methodCall->args, $scope),
                ),
                'in' => fn () => new ObjectType(
                    className: In::class,
                    constructorArgs: PhpFunctionArgument::list($methodCall->args, $scope),
                ),
            ];

            if (isset($methods[$methodCall->name->name])) {
                return $methods[$methodCall->name->name]();
            }
        }

        return null;
    }
}

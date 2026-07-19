<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;

/**
 * Handles static calls on `Illuminate\Validation\Rule` class.
 */
class ValidationRuleStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->className === Rule::class) {
            $methods = [
                'enum' => fn () => new ObjectType(
                    className: Enum::class,
                    constructorArgs: $call->argTypes,
                ),
                'in' => fn () => new ObjectType(
                    className: In::class,
                    constructorArgs: $call->argTypes,
                ),
            ];

            if (isset($methods[$call->methodName])) {
                return $methods[$call->methodName]();
            }
        }

        return null;
    }
}

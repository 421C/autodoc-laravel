<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallExtension;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;

/**
 * Handles static calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelStaticCall extends StaticCallExtension
{
    public function getReturnType(StaticCall $methodCall, Scope $scope): ?Type
    {
        $methods = [
            'insert' => fn () => new BoolType,
            // 'find' => fn () => ,
        ];

        if ($methodCall->name instanceof Node\Identifier
            && isset($methods[$methodCall->name->name])
            && $methodCall->class instanceof Node\Name
            && $scope->getResolvedClassName($methodCall->class) === Model::class
        ) {
            return $methods[$methodCall->name->name]();
        }

        return null;
    }
}

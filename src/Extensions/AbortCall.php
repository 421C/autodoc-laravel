<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NeverType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\FuncCallContext;
use AutoDoc\Extensions\FuncCallExtension;

/**
 * Handles `abort(...)`, `abort_if(...)` and `abort_unless(...)`.
 */
class AbortCall extends FuncCallExtension
{
    private const CODE_PARAM_INDEX = [
        'abort' => 0,
        'abort_if' => 1,
        'abort_unless' => 1,
    ];

    public function getReturnType(FuncCallContext $call): ?Type
    {
        if ($call->functionName === 'abort') {
            return new NeverType;
        }

        return null;
    }

    public function handleSideEffect(FuncCallContext $call): void
    {
        if ($call->functionName === null || ! $call->scope->route) {
            return;
        }

        $codeParamIndex = self::CODE_PARAM_INDEX[$call->functionName] ?? null;

        if ($codeParamIndex === null) {
            return;
        }

        $codeArgIndex = $call->argTypes->indexForParameter('code', $codeParamIndex);

        if ($codeArgIndex === null) {
            return;
        }

        $codeType = $call->argTypes->get($codeArgIndex)->unwrapType($call->scope->config);

        if (! $codeType instanceof IntegerType) {
            return;
        }

        foreach ($codeType->getPossibleValues() ?? [] as $statusCode) {
            $call->scope->route->addResponse(
                status: $statusCode,
                contentType: 'application/json',
                body: new ObjectType(properties: [
                    'message' => $this->getMessageType($call, $codeParamIndex + 1)->setRequired(true),
                ]),
            );
        }
    }


    private function getMessageType(FuncCallContext $call, int $messageParamIndex): Type
    {
        $messageArgIndex = $call->argTypes->indexForParameter('message', $messageParamIndex);

        if ($messageArgIndex === null) {
            return new StringType;
        }

        $messageType = $call->argTypes->get($messageArgIndex)->unwrapType($call->scope->config);

        if ($messageType instanceof StringType && $messageType->value !== null && $messageType->value !== '') {
            return new StringType(value: $messageType->value);
        }

        return new StringType;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\ResolvesBinaryResponseContentType;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles a literal `Content-Type` header in `response()->download()`/`file()`/`stream()`/`streamDownload()`.
 */
class BinaryResponseMethodCall extends MethodCallExtension
{
    use ResolvesBinaryResponseContentType;

    /**
     * Method name => [response class, `$headers` parameter index].
     */
    private const array METHODS = [
        'download' => [BinaryFileResponse::class, 2],
        'file' => [BinaryFileResponse::class, 1],
        'stream' => [StreamedResponse::class, 2],
        'streamDownload' => [StreamedResponse::class, 2],
    ];

    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! isset(self::METHODS[$call->methodName])) {
            return null;
        }

        $receiverType = $call->getVarType()->unwrapType($call->scope->config);

        if (! ($receiverType instanceof ObjectType)
            || $receiverType->className === null
            || ! is_a($receiverType->className, ResponseFactory::class, true)
        ) {
            return null;
        }

        [$responseClassName, $headersIndex] = self::METHODS[$call->methodName];

        return $this->binaryResponseTypeWithContentType($responseClassName, $headersIndex, $call->argTypes, $call->scope);
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\Helpers\ResolvesBinaryResponseContentType;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles a literal `Content-Type` header in `Storage::download()`/`response()`.
 */
class BinaryResponseStaticCall extends StaticCallExtension
{
    use ResolvesBinaryResponseContentType;

    private const int HEADERS_INDEX = 2;

    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->className !== Storage::class || ! in_array($call->methodName, ['download', 'response'], true)) {
            return null;
        }

        return $this->binaryResponseTypeWithContentType(StreamedResponse::class, self::HEADERS_INDEX, $call->argTypes, $call->scope);
    }
}

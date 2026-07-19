<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Http\Response;

class ResponseController
{
    #[ExpectedOperationSchema([
        'responses' => [
            204 => [
                'description' => '',
            ],
        ],
    ])]
    public function noContent(): Response
    {
        return response()->noContent();
    }


    #[ExpectedOperationSchema([
        'responses' => [
            418 => [
                'description' => '',
            ],
        ],
    ])]
    public function noContentWithCustomStatus(): Response
    {
        return response()->noContent(status: 418);
    }
}

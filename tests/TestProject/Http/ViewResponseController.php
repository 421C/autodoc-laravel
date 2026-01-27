<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\View\View;

/**
 * Tests for view/HTML responses.
 */
class ViewResponseController
{
    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'text/html' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function viewResponse(): View
    {
        return view('laravel-exceptions::419');
    }
}

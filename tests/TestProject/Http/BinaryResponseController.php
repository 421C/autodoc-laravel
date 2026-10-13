<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BinaryResponseController
{
    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    '*/*' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function download(): mixed
    {
        return response()->download(storage_path('reports/summary.pdf'), 'summary.pdf');
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    '*/*' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function file(): BinaryFileResponse
    {
        return response()->file(storage_path('images/photo.jpg'), ['Cache-Control' => 'private, max-age=3600']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    '*/*' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function streamDownload(): mixed
    {
        return response()->streamDownload(function () {
            echo 'id,name';
        }, 'export.csv');
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    '*/*' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function storageDownload(): mixed
    {
        return Storage::download('reports/summary.pdf');
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    '*/*' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function storageResponse(): mixed
    {
        return Storage::response('images/photo.jpg');
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/pdf' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function downloadWithContentType(): mixed
    {
        return response()->download(storage_path('reports/summary.pdf'), 'summary.pdf', ['Content-Type' => 'application/pdf']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'image/jpeg' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function fileWithContentType(): BinaryFileResponse
    {
        return response()->file(storage_path('images/photo.jpg'), headers: ['content-type' => 'image/jpeg']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'text/csv' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function streamDownloadWithContentType(): mixed
    {
        return response()->streamDownload(function () {
            echo 'id,name';
        }, 'export.csv', ['Content-Type' => 'text/csv']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/pdf' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function storageDownloadWithContentType(): mixed
    {
        return Storage::download('reports/summary.pdf', 'summary.pdf', ['Content-Type' => 'application/pdf']);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    '*/*' => [
                        'schema' => [
                            'type' => 'string',
                            'format' => 'binary',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function fileWithNonLiteralContentType(): BinaryFileResponse
    {
        return response()->file(storage_path('images/photo.jpg'), ['Content-Type' => (string) getenv('IMAGE_MIME')]);
    }


    #[ExpectedOperationSchema([
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function streamJson(): mixed
    {
        return response()->streamJson(['status' => 'ok']);
    }
}

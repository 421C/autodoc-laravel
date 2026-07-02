<?php declare(strict_types=1);

use AutoDoc\Laravel\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;


/** @var ?string */
$url = config('autodoc.laravel.url');

if ($url) {
    /** @var string[] */
    $middleware = config('autodoc.laravel.middleware', []);

    Route::prefix($url)
        ->name('autodoc.')
        ->middleware($middleware)
        ->group(function () {

            /**
             * Single catch-all that serves the docs HTML page, the vendored
             * viewer assets (`assets/<file>`) and the workspace OpenAPI JSON
             * (`openapi.json`) through `DocViewer::handle()`.
             */
            Route::get('{path?}', [DocsController::class, 'handle'])
                ->where('path', '.*')
                ->name('view');

        });
}

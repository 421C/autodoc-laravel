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

            Route::get('/', [DocsController::class, 'getView'])->name('view');
            Route::get('/openapi-json', [DocsController::class, 'getJson'])->name('openapi-json');

        });
}

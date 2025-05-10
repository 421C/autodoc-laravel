<?php declare(strict_types=1);

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Http\Controller;
use AutoDoc\Laravel\Tests\TestProject\Http\InvokableController;
use Illuminate\Support\Facades\Route;

Route::get('/test/1', [Controller::class, 'route1']);
Route::get('/test/2', [Controller::class, 'route2'])->name('test2');
Route::get('/test/3', 'AutoDoc\Laravel\Tests\TestProject\Http\Controller@route3');
Route::get('/test/4', [Controller::class, 'route4']);
Route::get('/test/5', [Controller::class, 'route5']);
Route::get('/test/6', [Controller::class, 'route6']);
Route::get('/test/7', [Controller::class, 'route7']);
Route::get('/test/8', [Controller::class, 'route8']);
Route::get('/test/9', [Controller::class, 'route9']);
Route::get('/test/10', [Controller::class, 'route10']);
Route::get('/test/11', [Controller::class, 'route11']);
Route::get('/test/12', [Controller::class, 'route12']);
Route::get('/test/13', [Controller::class, 'route13']);

Route::get('/test/invoke', InvokableController::class);

Route::get('/test/callable1', (
    /**
     * @return object{test: int}
     */
    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'requestBody' => null,
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'test' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'test',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
        ],
    ])] function () {
        request()->validate([
            'email' => 'required|email',
        ]);
    }
));

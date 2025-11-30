<?php declare(strict_types=1);

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\RocketCategory;
use AutoDoc\Laravel\Tests\TestProject\Http\Controller;
use AutoDoc\Laravel\Tests\TestProject\Http\InvokableController;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
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
Route::get('/test/14', [Controller::class, 'route14']);
Route::get('/test/15', [Controller::class, 'route15']);
Route::get('/test/16', [Controller::class, 'route16']);
Route::get('/test/17', [Controller::class, 'route17']);
Route::get('/test/18/{rocket}', [Controller::class, 'route18']);
Route::get('/test/19/{state}', [Controller::class, 'route19']);
Route::get('/test/20/{rocketId}', [Controller::class, 'route20']);
Route::get('/test/21', [Controller::class, 'route21']);
Route::get('/test/22', [Controller::class, 'route22']);
Route::get('/test/23', [Controller::class, 'route23']);
Route::get('/test/24', [Controller::class, 'route24']);
Route::get('/test/25', [Controller::class, 'route25']);
Route::get('/test/26', [Controller::class, 'route26']);
Route::get('/test/27', [Controller::class, 'route27']);
Route::get('/test/28', [Controller::class, 'route28']);
Route::get('/test/29', [Controller::class, 'route29']);
Route::get('/test/30', [Controller::class, 'route30']);
Route::get('/test/31', [Controller::class, 'route31']);
Route::get('/test/32', [Controller::class, 'route32']);
Route::get('/test/33', [Controller::class, 'route33']);
Route::get('/test/34', [Controller::class, 'route34']);
Route::get('/test/35', [Controller::class, 'route35']);
Route::get('/test/36', [Controller::class, 'route36']);
Route::get('/test/37', [Controller::class, 'route37']);
Route::get('/test/38', [Controller::class, 'route38']);
Route::get('/test/39', [Controller::class, 'route39']);
Route::get('/test/40', [Controller::class, 'route40']);
Route::get('/test/41', [Controller::class, 'route41']);
Route::get('/test/42', [Controller::class, 'route42']);
Route::get('/test/43', [Controller::class, 'route43']);
Route::get('/test/44', [Controller::class, 'route44']);
Route::get('/test/45', [Controller::class, 'route45']);
Route::get('/test/46', [Controller::class, 'route46']);
Route::get('/test/47', [Controller::class, 'route47']);
Route::get('/test/48', [Controller::class, 'route48']);
Route::get('/test/49', [Controller::class, 'route49']);
Route::get('/test/50', [Controller::class, 'route50']);
Route::get('/test/51', [Controller::class, 'route51']);
Route::get('/test/52', [Controller::class, 'route52']);

Route::get('/test/invoke', InvokableController::class);

Route::get('/test/closure1', (
    /**
     * @return object{test: int}
     */
    #[ExpectedOperationSchema([
        'summary' => '',
        'description' => '',
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

Route::get('/test/closure2/{category}/search/{rocket:launch_date?}', (
    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'category',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'description' => '[RocketCategory](#/schemas/RocketCategory)',
                    'enum' => [
                        'big',
                        'small',
                    ],
                ],
            ],
            [
                'in' => 'path',
                'name' => 'rocket',
                'schema' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
            ],
        ],
    ])] function (RocketCategory $category, Rocket $rocket = null) {}
));

Route::get('/test/closure3/{uuid}/{name}/{version}', (
    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'uuid',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            [
                'in' => 'path',
                'name' => 'name',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'pattern' => '^[a-zA-Z0-9]+$',
                ],
            ],
            [
                'in' => 'path',
                'name' => 'version',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                ],
            ],
        ],
    ])] function (string $uuid, string $name, int $version) {}
))->whereUuid('uuid')->whereAlphaNumeric('name');

<?php declare(strict_types=1);

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\RocketCategory;
use AutoDoc\Laravel\Tests\TestProject\Http\Controller;
use AutoDoc\Laravel\Tests\TestProject\Http\InvokableController;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
use Illuminate\Support\Facades\Route;

Route::post('/test/1', [Controller::class, 'route1']);
Route::post('/test/2', [Controller::class, 'route2'])->name('test2');
Route::post('/test/3', 'AutoDoc\Laravel\Tests\TestProject\Http\Controller@route3');
Route::post('/test/4', [Controller::class, 'route4']);
Route::post('/test/5', [Controller::class, 'route5']);
Route::post('/test/6', [Controller::class, 'route6']);
Route::post('/test/7', [Controller::class, 'route7']);
Route::post('/test/8', [Controller::class, 'route8']);
Route::post('/test/9', [Controller::class, 'route9']);
Route::post('/test/10', [Controller::class, 'route10']);
Route::post('/test/11', [Controller::class, 'route11']);
Route::post('/test/12', [Controller::class, 'route12']);
Route::post('/test/13', [Controller::class, 'route13']);
Route::post('/test/14', [Controller::class, 'route14']);
Route::post('/test/15', [Controller::class, 'route15']);
Route::post('/test/16', [Controller::class, 'route16']);
Route::post('/test/17', [Controller::class, 'route17']);
Route::post('/test/18/{rocket}', [Controller::class, 'route18']);
Route::post('/test/19/{state}', [Controller::class, 'route19']);
Route::post('/test/20/{rocketId}', [Controller::class, 'route20']);
Route::post('/test/21', [Controller::class, 'route21']);
Route::post('/test/22', [Controller::class, 'route22']);
Route::post('/test/23', [Controller::class, 'route23']);
Route::post('/test/24', [Controller::class, 'route24']);
Route::post('/test/25', [Controller::class, 'route25']);
Route::post('/test/26', [Controller::class, 'route26']);
Route::post('/test/27', [Controller::class, 'route27']);
Route::post('/test/28', [Controller::class, 'route28']);
Route::post('/test/29', [Controller::class, 'route29']);
Route::post('/test/30', [Controller::class, 'route30']);
Route::post('/test/31', [Controller::class, 'route31']);
Route::post('/test/32', [Controller::class, 'route32']);
Route::post('/test/33', [Controller::class, 'route33']);
Route::post('/test/34', [Controller::class, 'route34']);
Route::post('/test/35', [Controller::class, 'route35']);
Route::post('/test/36', [Controller::class, 'route36']);
Route::post('/test/37', [Controller::class, 'route37']);
Route::post('/test/38', [Controller::class, 'route38']);
Route::post('/test/39', [Controller::class, 'route39']);
Route::post('/test/40', [Controller::class, 'route40']);
Route::post('/test/41', [Controller::class, 'route41']);
Route::post('/test/42', [Controller::class, 'route42']);
Route::post('/test/43', [Controller::class, 'route43']);
Route::post('/test/44', [Controller::class, 'route44']);
Route::post('/test/45', [Controller::class, 'route45']);
Route::post('/test/46', [Controller::class, 'route46']);
Route::post('/test/47', [Controller::class, 'route47']);
Route::post('/test/48', [Controller::class, 'route48']);
Route::post('/test/49', [Controller::class, 'route49']);
Route::post('/test/50', [Controller::class, 'route50']);
Route::post('/test/51', [Controller::class, 'route51']);
Route::post('/test/52', [Controller::class, 'route52']);
Route::post('/test/53', [Controller::class, 'route53']);
Route::get('/test/54', [Controller::class, 'route54']);
Route::post('/test/55', [Controller::class, 'route55']);
Route::post('/test/56', [Controller::class, 'route56']);
Route::post('/test/57', [Controller::class, 'route57']);
Route::post('/test/58', [Controller::class, 'route58']);
Route::post('/test/59', [Controller::class, 'route59']);
Route::post('/test/60', [Controller::class, 'route60']);

Route::post('/test/invoke', InvokableController::class);

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

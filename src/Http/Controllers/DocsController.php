<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Http\Controllers;

use AutoDoc\DocViewer;
use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Workspace;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;


class DocsController extends Controller
{
    public function getView(): void
    {
        $openApiUrl = route('autodoc.openapi-json', [
            'token' => request('token'),
        ]);

        /** @var string */
        $title = config('autodoc.api.title', '');

        /** @var string */
        $theme = config('autodoc.ui.theme', 'light');

        /** @var string */
        $logo = config('autodoc.ui.logo', '');

        /** @var bool */
        $hideTryIt = config('autodoc.ui.hide_try_it', false);

        $docViewer = new DocViewer(
            title: $title,
            openApiUrl: $openApiUrl,
            theme: $theme,
            logo: $logo,
            hideTryIt: $hideTryIt,
        );

        $docViewer->renderPage();
    }


    public function getJson(): Response
    {
        /** @var ?string */
        $accessToken = request('token');

        $config = (new ConfigLoader)->load();

        if ($accessToken) {
            $workspace = Workspace::findUsingToken($accessToken, $config);

        } else {
            $workspace = Workspace::getDefault($config);
        }


        if (! $workspace) {
            if ($accessToken) {
                abort(403);

            } else {
                abort(404);
            }
        }

        return response($workspace->getJson(), 200)
            ->header('Content-Type', 'application/json');
    }
}

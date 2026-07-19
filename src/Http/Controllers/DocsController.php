<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Http\Controllers;

use AutoDoc\Config;
use AutoDoc\DocViewer;
use AutoDoc\DocViewerResponse;
use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Workspace;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocsController extends Controller
{
    /**
     * Single front controller for the docs route. From one wildcard it serves
     * the HTML page, the vendored viewer assets and the workspace OpenAPI JSON.
     */
    public function handle(string $path = ''): Response|BinaryFileResponse
    {
        $config = (new ConfigLoader)->load();
        $normalizedPath = trim($path, '/');

        $workspace = $this->resolveWorkspace($config);

        /** @var string */
        $baseUrl = config('autodoc.laravel.url', '');

        $docViewer = new DocViewer($config, baseUrl: url($baseUrl), workspaceKey: $workspace->key);

        /** @var ?string */
        $accessToken = request('token');

        /**
         * Carry the per-client access token onto the page's OpenAPI JSON URL so
         * the viewer's fetch keeps resolving to that client's workspace. Served
         * wiki pages (`path`-backed `wiki/<id>` URLs) are gated the same way, so
         * carry the token onto them too; external `url` entries stay untouched.
         */
        if ($accessToken !== null && $accessToken !== '') {
            $docViewer->openApiUrl .= '?token=' . urlencode($accessToken);

            $wikiBase = rtrim(url($baseUrl), '/') . '/wiki/';

            foreach ($docViewer->wikiPages as $i => $page) {
                if (str_starts_with($page['url'], $wikiBase)) {
                    $docViewer->wikiPages[$i]['url'] .= '?token=' . urlencode($accessToken);
                }
            }
        }

        return $this->toResponse($docViewer->handle($normalizedPath));
    }

    private function toResponse(DocViewerResponse $result): Response|BinaryFileResponse
    {
        if ($result->filePath !== null) {
            return new BinaryFileResponse($result->filePath, $result->status, $result->headers);
        }

        return new Response($result->body ?? '', $result->status, $result->headers);
    }

    private function resolveWorkspace(Config $config): Workspace
    {
        /** @var ?string */
        $accessToken = request('token');

        if ($accessToken !== null && $accessToken !== '') {
            $workspace = Workspace::findUsingToken($accessToken, $config);

            if (! $workspace) {
                abort(403);
            }

            return $workspace;
        }

        $workspace = Workspace::getDefault($config);

        if (! $workspace) {
            abort(404);
        }

        return $workspace;
    }
}

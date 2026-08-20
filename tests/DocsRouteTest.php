<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\DocViewer;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * HTTP-level coverage for the docs front controller.
 */
class DocsRouteTest extends \Orchestra\Testbench\TestCase
{
    private const string DOCS_URL = '/docs/api';

    private const string ACCESS_TOKEN = 'workspace-access-token';

    protected function getPackageProviders($app)
    {
        return [
            AutoDocServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('autodoc.laravel', [
            'url' => self::DOCS_URL,
            'middleware' => [],
            'autoload_builtin_extensions' => false,
        ]);

        $app['config']->set('autodoc.workspaces', [
            'gated-client' => [
                'routes' => ['/test/'],
                'access_token' => self::ACCESS_TOKEN,
            ],
        ]);
    }

    #[Test]
    #[TestWith(['autodoc-viewer.css', 'text/css; charset=utf-8'])]
    #[TestWith(['autodoc-viewer.js', 'text/javascript; charset=utf-8'])]
    public function viewerAssetIsServedWithoutAnAccessToken(string $file, string $contentType): void
    {
        $path = $this->assetPath($file);

        $response = $this->get(self::DOCS_URL . '/assets/' . $file . '?id=' . filemtime($path));

        $response->assertOk();
        $response->assertHeader('Content-Type', $contentType);
        $response->assertHeader('Content-Length', (string) filesize($path));

        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function viewerAssetIsServedEvenWithAnInvalidAccessToken(): void
    {
        $response = $this->get(self::DOCS_URL . '/assets/autodoc-viewer.css?token=nope');

        $response->assertOk();
        $response->assertHeader('Content-Length', (string) filesize($this->assetPath('autodoc-viewer.css')));
    }

    #[Test]
    public function unknownViewerAssetIsNotFound(): void
    {
        $this->get(self::DOCS_URL . '/assets/does-not-exist.css')->assertNotFound();
    }

    #[Test]
    public function docsPageIsNotFoundWithoutAnAccessToken(): void
    {
        $this->get(self::DOCS_URL)->assertNotFound();
    }

    #[Test]
    public function docsPageIsForbiddenWithAnInvalidAccessToken(): void
    {
        $this->get(self::DOCS_URL . '?token=nope')->assertForbidden();
    }

    #[Test]
    public function docsPageIsServedWithAValidAccessTokenAndLinksTokenlessAssets(): void
    {
        $response = $this->get(self::DOCS_URL . '?token=' . self::ACCESS_TOKEN);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=utf-8');

        $response->assertSee(self::DOCS_URL . '/openapi.json?token=' . self::ACCESS_TOKEN, escape: false);

        foreach (['autodoc-viewer.css', 'autodoc-viewer.js'] as $file) {
            $response->assertSee(
                self::DOCS_URL . '/assets/' . $file . '?id=' . filemtime($this->assetPath($file)),
                escape: false,
            );
        }

        $this->assertStringNotContainsString(
            '/assets/autodoc-viewer.css?id=' . filemtime($this->assetPath('autodoc-viewer.css')) . '&token=',
            $response->getContent() ?: '',
        );
    }

    #[Test]
    #[TestWith(['openapi.json'])]
    #[TestWith(['wiki/intro'])]
    public function workspaceScopedPathStaysGated(string $path): void
    {
        $this->get(self::DOCS_URL . '/' . $path)->assertNotFound();
        $this->get(self::DOCS_URL . '/' . $path . '?token=nope')->assertForbidden();
    }

    #[Test]
    public function tokenlessWorkspaceStillServesBothThePageAndTheAssets(): void
    {
        config()->set('autodoc.workspaces', [
            'open-client' => ['routes' => ['/test/']],
        ]);

        $this->get(self::DOCS_URL)->assertOk();
        $this->get(self::DOCS_URL . '/assets/autodoc-viewer.css')->assertOk();
    }

    private function assetPath(string $file): string
    {
        $path = DocViewer::getAssetPath($file);

        $this->assertNotNull($path, "Vendored viewer asset `{$file}` is missing.");

        return (string) $path;
    }
}

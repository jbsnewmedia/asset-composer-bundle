<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AssetComposerAppAssetsTest extends TestCase
{
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/asset-composer-app-'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->projectDir.'/assets');
    }

    #[Test]
    public function getAssetFileProcessesCssUrlsForAppNamespaceWithExistingQuery(): void
    {
        $cssDir = $this->projectDir.'/assets';
        $cssPath = $cssDir.'/style.css';
        $iconPath = $cssDir.'/icon.svg';

        // CSS mit bereits vorhandenem Query-Parameter und absoluten/inline URLs, die übersprungen werden sollen
        $cssContent = "body{background:url('icon.svg?x=1');} .a{background:url(data:image/png;base64,AAAA)} .b{background:url('http://example.com/x.png')} ";
        file_put_contents($cssPath, $cssContent);
        file_put_contents($iconPath, '<svg></svg>');

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/assets/style.css');

        $assetComposer = new AssetComposer(
            $this->projectDir,
            $router,
            'dev',
            'secret',
            [],
            true,
        );

        $mtime = filemtime($cssPath);
        $v = md5('app/assets/style.css#secret#'.$mtime);

        $response = $assetComposer->getAssetFile('app', 'assets', 'style.css', $v);
        $content = $response->getContent();

        // icon.svg sollte versioniert werden und Query-Parameter beibehalten (&v=...)
        $this->assertStringContainsString("icon.svg?x=1&v=", $content);
        // data: und http:// URLs bleiben unverändert
        $this->assertStringContainsString('url(data:image/png', $content);
        $this->assertStringContainsString("url('http://example.com/x.png')", $content);
    }

    #[Test]
    public function getAssetFileNameGeneratesAbsoluteUrlWhenConfigured(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with(
                'jbs_new_media_assets_composer',
                [
                    'namespace' => 'test',
                    'package' => 'package',
                    'asset' => 'file.css',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )->willReturn('https://example.org/ac/test/package/file.css');

        // Lege die Datei an, damit filemtime funktioniert
        $vendorDir = $this->projectDir.'/vendor/test/package';
        $this->filesystem->mkdir($vendorDir);
        file_put_contents($vendorDir.'/file.css', 'body{}');

        $assetComposer = new AssetComposer(
            $this->projectDir,
            $router,
            'dev',
            'secret',
            ['/vendor/'],
            false, // absolute Url
        );

        $result = $assetComposer->getAssetFileName('test/package/file.css');
        $this->assertStringStartsWith('https://example.org/ac/test/package/file.css?v=', $result);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $this->filesystem->remove($this->projectDir);
        }
    }
}

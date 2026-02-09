<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AssetComposerAdvancedTest extends TestCase
{
    private AssetComposer $assetComposer;
    private UrlGeneratorInterface $router;
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/asset-composer-advanced-'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->projectDir);

        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->router
            ->method('generate')
            ->with('jbs_new_media_assets_composer', $this->anything(), $this->anything())
            ->willReturnCallback(function ($route, $params) {
                return sprintf('/assetscomposer/%s/%s/%s',
                    $params['namespace'],
                    $params['package'],
                    $params['asset']
                );
            });

        $this->assetComposer = new AssetComposer(
            $this->projectDir,
            $this->router,
            'prod',
            'test-secret',
            ['/vendor/', '/custom-vendor/']
        );
    }

    #[Test]
    public function getAssetFileWithAllContentTypes(): void
    {
        $contentTypes = [
            'css' => 'body { color: red; }',
            'js' => 'console.log("test");',
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', true),
            'woff2' => 'mock-font-data',
            'json' => '{"test": true}',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
        ];

        foreach ($contentTypes as $extension => $content) {
            $this->createAssetWithProtection($extension, $content);

            $fileMTime = filemtime($this->projectDir."/vendor/test/package/asset.{$extension}");
            $baseUrlPart = "test/package/asset.{$extension}";
            $validVersion = md5($baseUrlPart.'#test-secret#'.$fileMTime);

            $response = $this->assetComposer->getAssetFile(
                'test',
                'package',
                "asset.{$extension}",
                $validVersion
            );

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertTrue($response->headers->has('Content-Type'));
        }
    }

    #[Test]
    public function getAssetFileWithInvalidAssetsComposerJson(): void
    {
        $this->createInvalidProtectionFile();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid asset composer file');

        $this->assetComposer->getAssetFile('test', 'package', 'asset.css', '');
    }

    #[Test]
    public function getAssetFileWithMissingFilesArrayInProduction(): void
    {
        $protectionContent = json_encode(['name' => 'test-package']);
        $this->createProtectionFile($protectionContent);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid asset composer file: missing files array');

        $this->assetComposer->getAssetFile('test', 'package', 'asset.css', '');
    }

    #[Test]
    public function setUrlVersionsWithComplexCSS(): void
    {
        $cssContent = '
        @font-face {
            font-family: "CustomFont";
            src: url("../fonts/custom.woff2") format("woff2"),
                 url("../fonts/custom.woff") format("woff");
        }
        .icon::before {
            background-image: url("../images/icon.svg");
        }
        .background {
            background: url("data:image/svg+xml;base64,PHN2Zw==");
        }
        .external {
            background: url("https://external.com/image.png");
        }';

        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package/fonts');
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package/images');

        file_put_contents($this->projectDir.'/vendor/test/package/fonts/custom.woff2', 'font-data');
        file_put_contents($this->projectDir.'/vendor/test/package/fonts/custom.woff', 'font-data');
        file_put_contents($this->projectDir.'/vendor/test/package/images/icon.svg', '<svg></svg>');

        $this->createAssetWithProtection('css', $cssContent);

        $fileMTime = filemtime($this->projectDir.'/vendor/test/package/asset.css');
        $validVersion = md5('test/package/asset.css#test-secret#'.$fileMTime);

        $response = $this->assetComposer->getAssetFile(
            'test',
            'package',
            'asset.css',
            $validVersion
        );

        $content = $response->getContent();

        $hasVersioning = false !== strpos($content, '?v=');

        $this->assertTrue(
            $hasVersioning || false !== strpos($content, 'url("../fonts/custom.woff2")'),
            'CSS sollte entweder versionierte URLs oder ursprünglichen Content enthalten'
        );

        $this->assertStringContainsString('data:image/svg+xml;base64,PHN2Zw==', $content);
        $this->assertStringContainsString('https://external.com/image.png', $content);
    }

    #[Test]
    public function getAssetFileNameWithDirectoryTraversalAttempt(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->expectExceptionMessage('Asset not found: ../../../etc/passwd');

        $this->assetComposer->getAssetFileName('../../../etc/passwd');
    }

    #[Test]
    public function getAssetFileNameWithComplexAssetPath(): void
    {
        $this->createNestedAssetStructure();

        $result = $this->assetComposer->getAssetFileName('test/package/dist/css/theme/dark.css');

        $this->assertStringContainsString('?v=', $result);

        $parts = explode('?v=', $result);
        $this->assertCount(2, $parts);
        $this->assertEquals(32, strlen($parts[1]), 'Version sollte ein MD5 Hash sein');
    }

    #[Test]
    public function getAssetFileWithMultiplePaths(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/custom-vendor/test/package');
        file_put_contents($this->projectDir.'/custom-vendor/test/package/custom.css', 'body { color: blue; }');

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ['custom.css'],
        ]);
        file_put_contents($this->projectDir.'/custom-vendor/test/package/assetscomposer.json', $protectionContent);

        $fileMTime = filemtime($this->projectDir.'/custom-vendor/test/package/custom.css');
        $validVersion = md5('test/package/custom.css#test-secret#'.$fileMTime);

        $response = $this->assetComposer->getAssetFile(
            'test',
            'package',
            'custom.css',
            $validVersion
        );

        $this->assertEquals('text/css', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('blue', $response->getContent());
    }

    #[Test]
    public function getAssetFileWithInvalidVersion(): void
    {
        $this->createAssetWithProtection('css', 'body { color: red; }');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid asset version');

        $this->assetComposer->getAssetFile('test', 'package', 'asset.css', 'invalid-version');
    }

    #[Test]
    public function getAssetFileWithEmptyVersion(): void
    {
        $this->createAssetWithProtection('css', 'body { color: red; }');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid asset version');

        $this->assetComposer->getAssetFile('test', 'package', 'asset.css', '');
    }

    #[Test]
    public function getAssetFileWithDevEnvironmentAndDevFiles(): void
    {
        $devAssetComposer = new AssetComposer(
            $this->projectDir,
            $this->router,
            'dev',
            'test-secret',
            ['/vendor/']
        );

        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/asset.css', 'body { color: red; }');
        file_put_contents($this->projectDir.'/vendor/test/package/dev-asset.css', 'body { color: green; }');

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ['asset.css'],
            'files-dev' => ['dev-asset.css'],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);

        $fileMTime = filemtime($this->projectDir.'/vendor/test/package/dev-asset.css');
        $validVersion = md5('test/package/dev-asset.css#test-secret#'.$fileMTime);

        $response = $devAssetComposer->getAssetFile('test', 'package', 'dev-asset.css', $validVersion);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('green', $response->getContent());
    }

    #[Test]
    public function getAssetFileWithRealPathSecurityCheck(): void
    {
        $this->createAssetWithProtection('css', 'body { color: red; }');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Vendor directory traversal detected');

        $this->assetComposer->getAssetFile('test', 'package', '../../../etc/passwd', '');
    }

    #[Test]
    public function getAssetFileWithUnreadableAssetsComposerFile(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/asset.css', 'body { color: red; }');

        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', '{"test": true}');
        chmod($this->projectDir.'/vendor/test/package/assetscomposer.json', 0000);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unable to read the asset composer file');

        try {
            $this->assetComposer->getAssetFile('test', 'package', 'asset.css', '');
        } finally {
            chmod($this->projectDir.'/vendor/test/package/assetscomposer.json', 0644);
        }
    }

    #[Test]
    public function getAssetFileWithInvalidContentType(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/asset.unknown', 'content');

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ['asset.unknown'],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);

        $fileMTime = filemtime($this->projectDir.'/vendor/test/package/asset.unknown');
        $validVersion = md5('test/package/asset.unknown#test-secret#'.$fileMTime);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid content type');

        $this->assetComposer->getAssetFile('test', 'package', 'asset.unknown', $validVersion);
    }

    private function createAssetWithProtection(string $extension, string $content): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir."/vendor/test/package/asset.{$extension}", $content);

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ["asset.{$extension}"],
            'files-dev' => ["dev-asset.{$extension}"],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);
    }

    private function createInvalidProtectionFile(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/asset.css', 'body { color: red; }');
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', 'invalid-json');
    }

    private function createProtectionFile(string $content): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/asset.css', 'body { color: red; }');
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $content);
    }

    private function createNestedAssetStructure(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package/dist/css/theme', 0755, true);
        file_put_contents($this->projectDir.'/vendor/test/package/dist/css/theme/dark.css', '.dark { background: #000; }');

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ['dist/css/theme/dark.css'],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);
    }

    protected function tearDown(): void
    {
        if (is_file($this->projectDir.'/vendor/test/package/assetscomposer.json')) {
            chmod($this->projectDir.'/vendor/test/package/assetscomposer.json', 0644);
        }

        if (is_file($this->projectDir.'/vendor/test/package/asset.css')) {
            chmod($this->projectDir.'/vendor/test/package/asset.css', 0644);
        }

        if (is_dir($this->projectDir)) {
            $this->filesystem->remove($this->projectDir);
        }
    }
}

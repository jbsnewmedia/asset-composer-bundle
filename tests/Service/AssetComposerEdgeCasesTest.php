<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AssetComposerEdgeCasesTest extends TestCase
{
    private AssetComposer $assetComposer;
    private UrlGeneratorInterface $router;
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/asset-composer-edge-'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->projectDir);

        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->router
            ->method('generate')
            ->willReturn('http://example.com/assetscomposer/test/package/asset.css');

        $this->assetComposer = new AssetComposer(
            $this->projectDir,
            $this->router,
            'prod',
            'test-secret',
            []
        );
    }

    #[Test]
    public function constructorWithDefaultPaths(): void
    {
        $assetComposer = new AssetComposer(
            $this->projectDir,
            $this->router,
            'prod',
            'test-secret'
        );

        $this->assertInstanceOf(AssetComposer::class, $assetComposer);
    }

    #[Test]
    public function getAssetFileWithAppAssets(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/assets');
        file_put_contents($this->projectDir.'/assets/app.css', 'body { color: blue; }');

        $fileMTime = filemtime($this->projectDir.'/assets/app.css');
        $validVersion = md5('app/assets/app.css#test-secret#'.$fileMTime);

        $response = $this->assetComposer->getAssetFile('app', 'assets', 'app.css', $validVersion);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/css', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('blue', $response->getContent());
    }

    #[Test]
    public function getAssetFileNameWithInvalidAssetPath(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset not found (invalid asset path)');

        $this->assetComposer->getAssetFileName('invalid');
    }

    #[Test]
    public function getAssetFileNameWithAppAssets(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/assets');
        file_put_contents($this->projectDir.'/assets/style.css', '.test { color: red; }');

        $result = $this->assetComposer->getAssetFileName('app/assets/style.css');

        $this->assertStringContainsString('?v=', $result);
    }

    #[Test]
    public function getAssetFileWithUnreadableAssetFile(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/asset.css', 'content');
        chmod($this->projectDir.'/vendor/test/package/asset.css', 0000);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unable to read the asset file');

        try {
            $fileMTime = filemtime($this->projectDir.'/vendor/test/package/asset.css');
            $validVersion = md5('test/package/asset.css#test-secret#'.$fileMTime);

            $this->assetComposer->getAssetFile('test', 'package', 'asset.css', $validVersion);
        } finally {
            chmod($this->projectDir.'/vendor/test/package/asset.css', 0644);
        }
    }

    #[Test]
    public function getAssetFileWithAssetNotInDevFiles(): void
    {
        $devAssetComposer = new AssetComposer(
            $this->projectDir,
            $this->router,
            'dev',
            'test-secret',
            ['/vendor/']
        );

        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/restricted.css', 'content');

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ['allowed.css'],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset not allowed');

        $devAssetComposer->getAssetFile('test', 'package', 'restricted.css', '');
    }

    #[Test]
    public function getAssetFileWithProdEnvironmentAndDevAsset(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/dev-only.css', 'content');

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ['prod.css'],
            'files-dev' => ['dev-only.css'],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset not allowed in production environment');

        $this->assetComposer->getAssetFile('test', 'package', 'dev-only.css', '');
    }

    #[Test]
    public function getAssetFileWithDirectoryTraversalAttempt(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Vendor directory traversal detected');

        $this->assetComposer->getAssetFile('test', 'package', '../../../etc/passwd', 'some-version');
    }

    #[Test]
    public function getAssetFileWithValidFileButFailedMTime(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Vendor directory not found');

        $this->assetComposer->getAssetFile('nonexistent', 'package', 'asset.css', 'some-version');
    }

    #[Test]
    public function getAssetFileWithMTimeFailureOnExistingFile(): void
    {
        $assetComposerWithVendorPath = new AssetComposer(
            $this->projectDir,
            $this->router,
            'prod',
            'test-secret',
            ['/vendor/']
        );

        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/asset.css', 'body { color: red; }');

        chmod($this->projectDir.'/vendor/test/package/asset.css', 0000);

        $this->expectException(BadRequestHttpException::class);

        try {
            $assetComposerWithVendorPath->getAssetFile('test', 'package', 'asset.css', 'some-version');
        } finally {
            chmod($this->projectDir.'/vendor/test/package/asset.css', 0644);
        }
    }

    #[Test]
    public function getAssetFileWithNonExistentFile(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset file not found');

        $this->assetComposer->getAssetFile('test', 'package', 'nonexistent.css', 'some-version');
    }

    #[Test]
    public function getAssetFileNameWithNonExistentAssetFile(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset not found: test/package/nonexistent.css');

        $this->assetComposer->getAssetFileName('test/package/nonexistent.css');
    }

    #[Test]
    public function getAssetFileWithActualMTimeFailure(): void
    {
        $assetComposerWithVendorPath = new AssetComposer(
            $this->projectDir,
            $this->router,
            'prod',
            'test-secret',
            ['/vendor/']
        );

        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset file not found');

        $assetComposerWithVendorPath->getAssetFile('test', 'package', 'missing.css', 'some-version');
    }

    #[Test]
    public function getAssetFileNameWithFileNotFoundInPaths(): void
    {
        $assetComposerWithPaths = new AssetComposer(
            $this->projectDir,
            $this->router,
            'prod',
            'test-secret',
            ['/vendor/', '/custom-path/']
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset not found: test/package/missing.css');

        $assetComposerWithPaths->getAssetFileName('test/package/missing.css');
    }

    #[Test]
    public function getAssetFileNameWithActualMTimeFailureForAppAssets(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset file not found: assets/temp.css');

        $this->assetComposer->getAssetFileName('app/assets/temp.css');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $this->filesystem->remove($this->projectDir);
        }
    }
}

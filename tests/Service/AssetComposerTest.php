<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Filesystem\Filesystem;

final class AssetComposerTest extends TestCase
{
    private AssetComposer $assetComposer;
    private UrlGeneratorInterface $router;
    private string $projectDir;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../Fixtures';
        $this->projectDir = sys_get_temp_dir() . '/asset-composer-test-' . uniqid();

        // Copy fixtures to test project dir
        $filesystem = new Filesystem();
        $filesystem->mirror($this->fixturesDir, $this->projectDir);

        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->assetComposer = new AssetComposer(
            $this->projectDir,
            $this->router,
            'dev',
            'test-secret',
            ['/vendor/']
        );
    }

    #[Test]
    public function getAssetFileSuccessfulWithFixtures(): void
    {
        $fileMTime = filemtime($this->projectDir . '/vendor/test/package/asset.css');
        $baseUrlPart = 'test/package/asset.css';
        $validVersion = md5($baseUrlPart . '#test-secret#' . $fileMTime);

        $response = $this->assetComposer->getAssetFile(
            'test',
            'package',
            'asset.css',
            $validVersion
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals('text/css', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        self::assertStringContainsString('color: red', $content);
        self::assertStringContainsString('font-size: 16px', $content);
    }

    #[Test]
    public function getAssetFileWithProtectionFileFromFixtures(): void
    {
        // assetscomposer.json ist bereits in Fixtures vorhanden
        $fileMTime = filemtime($this->projectDir . '/vendor/test/package/asset.css');
        $baseUrlPart = 'test/package/asset.css';
        $validVersion = md5($baseUrlPart . '#test-secret#' . $fileMTime);

        $response = $this->assetComposer->getAssetFile(
            'test',
            'package',
            'asset.css',
            $validVersion
        );

        self::assertInstanceOf(Response::class, $response);
    }

    #[Test]
    public function getAssetFileThrowsExceptionForNonExistentVendorDir(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Vendor directory not found');

        $this->assetComposer->getAssetFile(
            'non-existent',
            'package',
            'asset.css',
            ''
        );
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        if (is_dir($this->projectDir)) {
            $filesystem->remove($this->projectDir);
        }
    }
}

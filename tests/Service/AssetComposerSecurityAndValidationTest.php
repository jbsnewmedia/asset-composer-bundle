<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AssetComposerSecurityAndValidationTest extends TestCase
{
    private string $projectDir;
    private Filesystem $fs;
    private UrlGeneratorInterface $router;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/asset-composer-sec-'.uniqid();
        $this->fs = new Filesystem();
        $this->fs->mkdir($this->projectDir.'/vendor/test/package');

        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->router->method('generate')->willReturn('/ac/test/package/file.css');
    }

    private function createService(string $env = 'dev'): AssetComposer
    {
        return new AssetComposer(
            $this->projectDir,
            $this->router,
            $env,
            'secret',
            ['/vendor/']
        );
    }

    #[Test]
    public function getAssetFileThrowsOnSymlinkTraversal(): void
    {
        // Erstelle Datei außerhalb des vendor-Verzeichnisses
        $outsideDir = $this->projectDir.'/outside';
        $this->fs->mkdir($outsideDir);
        file_put_contents($outsideDir.'/outside.css', 'body{}');

        // Symlink innerhalb des vendor-Verzeichnisses zeigt nach außerhalb
        $symlinkPath = $this->projectDir.'/vendor/test/package/evil.css';
        @symlink($outsideDir.'/outside.css', $symlinkPath);
        $this->assertTrue(is_link($symlinkPath) || file_exists($symlinkPath), 'Symlink konnte nicht erstellt werden');

        $service = $this->createService('dev');

        $mtime = filemtime($symlinkPath);
        $v = md5('test/package/evil.css#secret#'.$mtime);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Vendor directory traversal detected');

        $service->getAssetFile('test', 'package', 'evil.css', $v);
    }

    #[Test]
    public function getAssetFileNameThrowsOnSymlinkTraversal(): void
    {
        // Erzeuge Ziel außerhalb des Projektverzeichnisses
        $outsideDir = sys_get_temp_dir().'/outside-'.uniqid();
        $this->fs->mkdir($outsideDir);
        file_put_contents($outsideDir.'/outside.css', 'body{}');

        $vendorDir = $this->projectDir.'/vendor/test/package';
        $this->fs->mkdir($vendorDir);
        $symlinkPath = $vendorDir.'/evil.css';
        @symlink($outsideDir.'/outside.css', $symlinkPath);
        $this->assertTrue(is_link($symlinkPath) || file_exists($symlinkPath));

        $service = $this->createService('dev');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Security violation: attempted directory traversal');

        $service->getAssetFileName('test/package/evil.css');
    }

    #[Test]
    public function getAssetFileThrowsWhenAssetsComposerMissingFilesInDev(): void
    {
        $vendorDir = $this->projectDir.'/vendor/test/package';
        file_put_contents($vendorDir.'/file.css', 'body{}');

        // assetscomposer.json ohne "files"
        file_put_contents($vendorDir.'/assetscomposer.json', json_encode(['name' => 'pkg']));

        $service = $this->createService('dev');
        $mtime = filemtime($vendorDir.'/file.css');
        $v = md5('test/package/file.css#secret#'.$mtime);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid asset composer file: missing files array');

        $service->getAssetFile('test', 'package', 'file.css', $v);
    }

    #[Test]
    public function getAssetFileThrowsWhenNotAllowedInDevWithFilesDevPresent(): void
    {
        $vendorDir = $this->projectDir.'/vendor/test/package';
        file_put_contents($vendorDir.'/file.css', 'body{}');

        // files und files-dev vorhanden aber Datei nicht erlaubt
        $data = [
            'files' => ['allowed.css'],
            'files-dev' => ['dev-allowed.css'],
        ];
        file_put_contents($vendorDir.'/assetscomposer.json', json_encode($data));

        $service = $this->createService('dev');
        $mtime = filemtime($vendorDir.'/file.css');
        $v = md5('test/package/file.css#secret#'.$mtime);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset not allowed in development environment');

        $service->getAssetFile('test', 'package', 'file.css', $v);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $this->fs->remove($this->projectDir);
        }
    }
}

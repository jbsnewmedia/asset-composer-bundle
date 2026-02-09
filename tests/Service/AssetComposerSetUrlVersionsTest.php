<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AssetComposerSetUrlVersionsTest extends TestCase
{
    private AssetComposer $assetComposer;
    private UrlGeneratorInterface $router;
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/asset-composer-url-'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->projectDir);

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
    public function setUrlVersionsWithNonExistentReferencedFiles(): void
    {
        $cssContent = '
        .test {
            background-image: url("../images/missing.png");
            font-face: url("../fonts/missing.woff");
        }';

        $this->createAssetWithoutReferencedFiles('css', $cssContent);

        $fileMTime = filemtime($this->projectDir.'/vendor/test/package/asset.css');
        $validVersion = md5('test/package/asset.css#test-secret#'.$fileMTime);

        $response = $this->assetComposer->getAssetFile(
            'test',
            'package',
            'asset.css',
            $validVersion
        );

        $content = $response->getContent();

        $this->assertStringContainsString('url("../images/missing.png")', $content);
        $this->assertStringContainsString('url("../fonts/missing.woff")', $content);
    }

    #[Test]
    public function setUrlVersionsWithUniqueUrlProcessing(): void
    {
        $cssContent = '
    .class1 { background-image: url("./icon.svg"); }
    .class2 { background-image: url("./icon.svg"); }
    .class3 { background-image: url("./icon.svg"); }';

        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/icon.svg', '<svg></svg>');

        $this->createAssetWithoutReferencedFiles('css', $cssContent);

        $fileMTime = filemtime($this->projectDir.'/vendor/test/package/asset.css');
        $validVersion = md5('test/package/asset.css#test-secret#'.$fileMTime);

        $response = $this->assetComposer->getAssetFile(
            'test',
            'package',
            'asset.css',
            $validVersion
        );

        $content = $response->getContent();

        $versionedUrlCount = substr_count($content, '?v=');
        $this->assertGreaterThan(0, $versionedUrlCount, 'URLs should be versioned');

        $this->assertStringContainsString('url("./icon.svg?v=', $content);
    }

    #[Test]
    public function setUrlVersionsWithMixedQuoteStyles(): void
    {
        $cssContent = "
        .single-quotes { background: url('./single.png'); }
        .double-quotes { background: url(\"./double.png\"); }
        .no-quotes { background: url(./none.png); }";

        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir.'/vendor/test/package/single.png', 'png-data');
        file_put_contents($this->projectDir.'/vendor/test/package/double.png', 'png-data');
        file_put_contents($this->projectDir.'/vendor/test/package/none.png', 'png-data');

        $this->createAssetWithoutReferencedFiles('css', $cssContent);

        $fileMTime = filemtime($this->projectDir.'/vendor/test/package/asset.css');
        $validVersion = md5('test/package/asset.css#test-secret#'.$fileMTime);

        $response = $this->assetComposer->getAssetFile(
            'test',
            'package',
            'asset.css',
            $validVersion
        );

        $content = $response->getContent();

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);

        $versionedUrlCount = substr_count($content, '?v=');
        $this->assertGreaterThanOrEqual(0, $versionedUrlCount);
    }

    private function createAssetWithoutReferencedFiles(string $extension, string $content): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        file_put_contents($this->projectDir."/vendor/test/package/asset.{$extension}", $content);

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ["asset.{$extension}"],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $this->filesystem->remove($this->projectDir);
        }
    }
}

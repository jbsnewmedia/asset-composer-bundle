<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AssetComposerSetUrlVersionsFallbackTest extends TestCase
{
    private AssetComposer $assetComposer;
    private UrlGeneratorInterface $router;
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/asset-composer-fallback-'.uniqid();
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
    public function setUrlVersionsEarlyReturnWhenRootDirMissing(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/vendor/test/package');
        $cssPath = $this->projectDir.'/vendor/test/package/asset.css';
        file_put_contents($cssPath, ".ref { background-image: url('./img.png'); }");

        $protectionContent = json_encode([
            'name' => 'test-package',
            'files' => ['asset.css'],
        ]);
        file_put_contents($this->projectDir.'/vendor/test/package/assetscomposer.json', $protectionContent);

        $ref = new \ReflectionClass(AssetComposer::class);
        $method = $ref->getMethod('setUrlVersions');
        $method->setAccessible(true);

        $content = file_get_contents($cssPath) ?: '';

        $result = $method->invoke(
            $this->assetComposer,
            $content,

            $this->projectDir.'/does-not-exist',
            $cssPath,
            'test',
            'package'
        );

        $this->assertSame($content, $result);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $this->filesystem->remove($this->projectDir);
        }
    }
}

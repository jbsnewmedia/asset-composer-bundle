<?php
// tests/Twig/AssetComposerExtensionAdvancedTest.php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Twig;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use JBSNewMedia\AssetComposerBundle\Twig\AssetComposerExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssetComposerExtensionAdvancedTest extends TestCase
{
    private AssetComposer $assetComposer;
    private AssetComposerExtension $twigExtension;

    protected function setUp(): void
    {
        $this->assetComposer = $this->createMock(AssetComposer::class);
        $this->twigExtension = new AssetComposerExtension($this->assetComposer);
    }

    #[Test]
    public function removeAssetComposerThrowsExceptionForMissingExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid asset: missing file extension');

        $this->twigExtension->removeAssetComposer('asset_without_extension');
    }

    #[Test]
    public function removeAssetComposerThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid asset type: pdf');

        $this->twigExtension->removeAssetComposer('document.pdf');
    }

    #[Test]
    public function renderStylesheetsWithEmptyPosition(): void
    {
        $result = $this->twigExtension->renderStylesheets('nonexistent_position');
        $this->assertEquals('', (string) $result);
    }

    #[Test]
    public function renderJavascriptsWithEmptyPosition(): void
    {
        $result = $this->twigExtension->renderJavascripts('nonexistent_position');
        $this->assertEquals('', (string) $result);
    }

    #[Test]
    public function getAssetComposerFileHandlesServiceException(): void
    {
        $this->assetComposer
            ->expects($this->once())
            ->method('getAssetFileName')
            ->with('test/package/nonexistent.css')
            ->willThrowException(new BadRequestHttpException('Asset not found'));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Asset not found');

        $this->twigExtension->getAssetComposerFile('test/package/nonexistent.css');
    }

    #[Test]
    public function addAndRemoveAssetComposerCycle(): void
    {
        $this->assetComposer
            ->method('getAssetFileName')
            ->willReturn('/assets/style.css?v=123');

        // Add asset
        $this->twigExtension->addAssetComposer('test/style.css', 'top');

        // Verify it's there
        $result = $this->twigExtension->renderStylesheets('top');
        $this->assertStringContainsString('<link rel="stylesheet"', (string) $result);

        // Remove asset
        $this->twigExtension->removeAssetComposer('test/style.css', 'top');

        // Verify it's gone
        $result = $this->twigExtension->renderStylesheets('top');
        $this->assertEquals('', (string) $result);
    }

    #[Test]
    public function htmlEscapingInAssetUrls(): void
    {
        $this->assetComposer
            ->method('getAssetFileName')
            ->willReturn('/assets/file.css?v=abc123&param="test"');

        $this->twigExtension->addAssetComposer('test/file.css');

        $result = $this->twigExtension->renderStylesheets();
        $content = (string) $result;

        // Should be properly escaped
        $this->assertStringContainsString('&amp;param=&quot;test&quot;', $content);
        $this->assertStringNotContainsString('&param="test"', $content);
    }
}

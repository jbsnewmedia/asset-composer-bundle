<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Twig;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use JBSNewMedia\AssetComposerBundle\Twig\AssetComposerExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Markup;
use Twig\TwigFunction;

class AssetComposerExtensionTest extends TestCase
{
    private AssetComposer $assetComposer;
    private AssetComposerExtension $twigExtension;

    protected function setUp(): void
    {
        $this->assetComposer = $this->createMock(AssetComposer::class);
        $this->twigExtension = new AssetComposerExtension($this->assetComposer);
    }

    #[Test]
    public function canBeInstantiated(): void
    {
        $this->assertInstanceOf(AssetComposerExtension::class, $this->twigExtension);
    }

    #[Test]
    public function getFunctions(): void
    {
        $functions = $this->twigExtension->getFunctions();

        $this->assertIsArray($functions);
        $this->assertCount(5, $functions);

        $functionNames = array_map(fn (TwigFunction $func) => $func->getName(), $functions);

        $this->assertContains('addAssetComposer', $functionNames);
        $this->assertContains('removeAssetComposer', $functionNames);
        $this->assertContains('renderAssetComposerStylesheets', $functionNames);
        $this->assertContains('renderAssetComposerJavascripts', $functionNames);
        $this->assertContains('getAssetComposerFile', $functionNames);
    }

    #[Test]
    public function addAssetComposerWithCss(): void
    {
        $this->assetComposer
            ->method('getAssetFileName')
            ->with('test/package/style.css')
            ->willReturn('/assets/style.css?v=123');

        $this->twigExtension->addAssetComposer('test/package/style.css', 'top');

        $result = $this->twigExtension->renderStylesheets('top');

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertStringContainsString('<link rel="stylesheet"', (string) $result);
        $this->assertStringContainsString('/assets/style.css?v=123', (string) $result);
    }

    #[Test]
    public function addAssetComposerWithJs(): void
    {
        $this->assetComposer
            ->method('getAssetFileName')
            ->with('test/package/script.js')
            ->willReturn('/assets/script.js?v=456');

        $this->twigExtension->addAssetComposer('test/package/script.js', 'bottom');

        $result = $this->twigExtension->renderJavascripts('bottom');

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertStringContainsString('<script src=', (string) $result);
        $this->assertStringContainsString('/assets/script.js?v=456', (string) $result);
    }

    #[Test]
    public function addAssetComposerThrowsExceptionForMissingExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid asset: missing file extension');

        $this->twigExtension->addAssetComposer('test/package/noextension');
    }

    #[Test]
    public function addAssetComposerThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid asset type: txt');

        $this->twigExtension->addAssetComposer('test/package/file.txt');
    }

    #[Test]
    public function removeAssetComposer(): void
    {
        $this->twigExtension->addAssetComposer('test/package/style.css');
        $this->twigExtension->removeAssetComposer('test/package/style.css');

        $result = $this->twigExtension->renderStylesheets();

        $this->assertEquals('', (string) $result);
    }

    #[Test]
    public function renderStylesheetsWithMultipleAssets(): void
    {
        $this->assetComposer
            ->method('getAssetFileName')
            ->willReturnMap([
                ['test/package/style1.css', '/assets/style1.css?v=123'],
                ['test/package/style2.css', '/assets/style2.css?v=456'],
            ]);

        $this->twigExtension->addAssetComposer('test/package/style1.css');
        $this->twigExtension->addAssetComposer('test/package/style2.css');

        $result = $this->twigExtension->renderStylesheets();

        $content = (string) $result;
        $this->assertStringContainsString('<link rel="stylesheet"', $content);
        $this->assertStringContainsString('/assets/style1.css?v=123', $content);
        $this->assertStringContainsString('/assets/style2.css?v=456', $content);
    }

    #[Test]
    public function getAssetComposerFile(): void
    {
        $this->assetComposer
            ->expects($this->once())
            ->method('getAssetFileName')
            ->with('test/package/asset.css')
            ->willReturn('/assets/asset.css?v=abc123');

        $result = $this->twigExtension->getAssetComposerFile('test/package/asset.css');

        $this->assertEquals('/assets/asset.css?v=abc123', $result);
    }

    #[Test]
    public function positionBasedAssetsIsolation(): void
    {
        $this->assetComposer
            ->method('getAssetFileName')
            ->willReturnMap([
                ['test/top.css', '/assets/top.css?v=789'],
                ['test/bottom.js', '/assets/bottom.js?v=012'],
            ]);

        $this->twigExtension->addAssetComposer('test/top.css', 'top');
        $this->twigExtension->addAssetComposer('test/bottom.js', 'bottom');

        $topResult = $this->twigExtension->renderStylesheets('top');
        $bottomResult = $this->twigExtension->renderJavascripts('bottom');
        $allResult = $this->twigExtension->renderStylesheets('all');

        $this->assertInstanceOf(Markup::class, $topResult);
        $this->assertInstanceOf(Markup::class, $bottomResult);
        $this->assertEquals('', (string) $allResult);
    }
}

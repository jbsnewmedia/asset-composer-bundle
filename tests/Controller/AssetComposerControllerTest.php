<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Controller;

use JBSNewMedia\AssetComposerBundle\Controller\AssetComposerController;
use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetComposerControllerTest extends TestCase
{
    private AssetComposer $assetComposer;
    private AssetComposerController $controller;

    protected function setUp(): void
    {
        $this->assetComposer = $this->createMock(AssetComposer::class);
        $this->controller = new AssetComposerController($this->assetComposer);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(AssetComposerController::class, $this->controller);
    }

    public function testGetAssetDelegatesCorrectly(): void
    {
        $request = new Request(['v' => 'test-version']);
        $expectedResponse = new Response('test content');

        $this->assetComposer
            ->expects($this->once())
            ->method('getAssetFile')
            ->with('test-namespace', 'test-package', 'test-asset.css', 'test-version')
            ->willReturn($expectedResponse);

        $result = $this->controller->getAsset(
            'test-namespace',
            'test-package',
            'test-asset.css',
            $request
        );

        $this->assertSame($expectedResponse, $result);
    }

    public function testGetAssetWithEmptyVersionParameter(): void
    {
        $request = new Request();
        $expectedResponse = new Response('test content');

        $this->assetComposer
            ->expects($this->once())
            ->method('getAssetFile')
            ->with('namespace', 'package', 'asset.js', '')
            ->willReturn($expectedResponse);

        $result = $this->controller->getAsset(
            'namespace',
            'package',
            'asset.js',
            $request
        );

        $this->assertSame($expectedResponse, $result);
    }

    public function testGetAssetWithComplexAssetPath(): void
    {
        $request = new Request(['v' => 'complex-version']);
        $expectedResponse = new Response('complex content');

        $this->assetComposer
            ->expects($this->once())
            ->method('getAssetFile')
            ->with('vendor', 'package', 'dist/css/style.min.css', 'complex-version')
            ->willReturn($expectedResponse);

        $result = $this->controller->getAsset(
            'vendor',
            'package',
            'dist/css/style.min.css',
            $request
        );

        $this->assertSame($expectedResponse, $result);
    }
}

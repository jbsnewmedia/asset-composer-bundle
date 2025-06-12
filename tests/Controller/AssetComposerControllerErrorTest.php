<?php
// tests/Controller/AssetComposerControllerErrorTest.php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Controller;

use JBSNewMedia\AssetComposerBundle\Controller\AssetComposerController;
use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssetComposerControllerErrorTest extends TestCase
{
    private AssetComposer $assetComposer;
    private AssetComposerController $controller;

    protected function setUp(): void
    {
        $this->assetComposer = $this->createMock(AssetComposer::class);
        $this->controller = new AssetComposerController($this->assetComposer);
    }

    public function testGetAssetHandlesServiceExceptions(): void
    {
        $request = new Request(['v' => 'invalid-version']);

        $this->assetComposer
            ->expects($this->once())
            ->method('getAssetFile')
            ->willThrowException(new BadRequestHttpException('Invalid version'));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid version');

        $this->controller->getAsset('test', 'package', 'asset.css', $request);
    }

    public function testGetAssetWithSpecialCharactersInPath(): void
    {
        $request = new Request(['v' => 'test-version']);
        $expectedResponse = new \Symfony\Component\HttpFoundation\Response('content');

        $this->assetComposer
            ->expects($this->once())
            ->method('getAssetFile')
            ->with('test-ns', 'test-pkg', 'special-chars_file.min.css', 'test-version')
            ->willReturn($expectedResponse);

        $result = $this->controller->getAsset(
            'test-ns',
            'test-pkg',
            'special-chars_file.min.css',
            $request
        );

        $this->assertSame($expectedResponse, $result);
    }
}

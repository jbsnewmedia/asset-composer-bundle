<?php

namespace JBSNewMedia\AssetComposerBundle\Tests\Controller;

use JBSNewMedia\AssetComposerBundle\Controller\AssetComposerController;
use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\TestCase;

class AssetComposerControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $service = $this->createMock(AssetComposer::class);
        $controller = new AssetComposerController($service);

        $this->assertInstanceOf(AssetComposerController::class, $controller);
    }
}

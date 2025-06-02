<?php

namespace JBSNewMedia\AssetComposerBundle\Tests\Twig;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use JBSNewMedia\AssetComposerBundle\Twig\AssetComposerExtension;
use PHPUnit\Framework\TestCase;

class AssetComposerExtensionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $service = $this->createMock(AssetComposer::class);
        $twigExtension = new AssetComposerExtension($service);

        $this->assertInstanceOf(AssetComposerExtension::class, $twigExtension);
    }
}

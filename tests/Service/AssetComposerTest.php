<?php

namespace JBSNewMedia\AssetComposerBundle\Tests\Service;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssetComposerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);

        $assetComposer = new AssetComposer(
            '/tmp', // Dummy projectDir
            $router,
            'dev', // environment
            'dummy-secret',
            ['/vendor/'] // paths
        );

        $this->assertInstanceOf(AssetComposer::class, $assetComposer);
    }
}

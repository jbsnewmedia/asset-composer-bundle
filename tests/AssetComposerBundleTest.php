<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests;

use JBSNewMedia\AssetComposerBundle\AssetComposerBundle;
use JBSNewMedia\AssetComposerBundle\DependencyInjection\AssetComposerExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssetComposerBundleTest extends TestCase
{
    #[Test]
    public function getContainerExtension(): void
    {
        $bundle = new AssetComposerBundle();
        $extension = $bundle->getContainerExtension();

        $this->assertInstanceOf(AssetComposerExtension::class, $extension);
        $this->assertEquals('asset_composer', $extension->getAlias());
    }

    #[Test]
    public function getContainerExtensionIsSingleton(): void
    {
        $bundle = new AssetComposerBundle();

        $extension1 = $bundle->getContainerExtension();
        $extension2 = $bundle->getContainerExtension();

        $this->assertSame($extension1, $extension2);
    }
}

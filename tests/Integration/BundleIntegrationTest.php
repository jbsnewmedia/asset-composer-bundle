<?php
// tests/Integration/BundleIntegrationTest.php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\Integration;

use JBSNewMedia\AssetComposerBundle\AssetComposerBundle;
use JBSNewMedia\AssetComposerBundle\DependencyInjection\AssetComposerExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

final class BundleIntegrationTest extends TestCase
{
    #[Test]
    public function bundleCanBeInstantiatedMultipleTimes(): void
    {
        $bundle1 = new AssetComposerBundle();
        $bundle2 = new AssetComposerBundle();

        $this->assertNotSame($bundle1, $bundle2);
        $this->assertEquals(
            $bundle1->getContainerExtension()->getAlias(),
            $bundle2->getContainerExtension()->getAlias()
        );
    }

    #[Test]
    public function extensionHandlesComplexConfiguration(): void
    {
        $extension = new AssetComposerExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '/tmp/test-project');

        $complexConfig = [
            'asset_composer' => [
                'paths' => [
                    '/vendor/',
                    '/node_modules/',
                    '/bower_components/',
                    '/custom-assets/'
                ]
            ]
        ];

        $extension->load($complexConfig, $container);

        $this->assertTrue($container->hasParameter('asset_composer.paths'));
        $paths = $container->getParameter('asset_composer.paths');

        $this->assertCount(4, $paths);
        $this->assertContains('/vendor/', $paths);
        $this->assertContains('/custom-assets/', $paths);
    }

    #[Test]
    public function prependHandlesExistingRoutesFile(): void
    {
        $projectDir = sys_get_temp_dir() . '/bundle-integration-' . uniqid();
        $filesystem = new Filesystem();

        try {
            // Setup
            $filesystem->mkdir($projectDir . '/config/routes');
            $existingContent = "existing_route:\n    path: /test";
            $filesystem->dumpFile($projectDir . '/config/routes/asset_composer.yaml', $existingContent);

            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $projectDir);

            $extension = new AssetComposerExtension();

            // Should not overwrite existing file
            $extension->prepend($container);

            $content = file_get_contents($projectDir . '/config/routes/asset_composer.yaml');
            $this->assertEquals($existingContent, $content);

        } finally {
            if (is_dir($projectDir)) {
                $filesystem->remove($projectDir);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests\DependencyInjection;

use JBSNewMedia\AssetComposerBundle\DependencyInjection\AssetComposerExtension;
use JBSNewMedia\AssetComposerBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

final class AssetComposerExtensionTest extends TestCase
{
    private AssetComposerExtension $extension;
    private ContainerBuilder $container;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->extension = new AssetComposerExtension();
        $this->container = new ContainerBuilder();
        $this->fixturesDir = __DIR__.'/../Fixtures';
        $this->container->setParameter('kernel.project_dir', '/tmp/test-project');
    }

    #[Test]
    public function loadWithDefaultConfiguration(): void
    {
        $this->extension->load([], $this->container);

        self::assertTrue($this->container->hasParameter('asset_composer.paths'));
        self::assertEquals([], $this->container->getParameter('asset_composer.paths'));
    }

    #[Test]
    public function loadWithCustomPaths(): void
    {
        $config = [
            'asset_composer' => [
                'paths' => ['/custom/path1/', '/custom/path2/'],
            ],
        ];

        $this->extension->load($config, $this->container);

        self::assertTrue($this->container->hasParameter('asset_composer.paths'));
        self::assertEquals(['/custom/path1/', '/custom/path2/'],
            $this->container->getParameter('asset_composer.paths'));
    }

    #[Test]
    public function prependCopiesRouteFileFromFixtures(): void
    {
        $testProjectDir = sys_get_temp_dir().'/test-project-'.uniqid();
        $bundleRoutesFile = __DIR__.'/../../config/routes.yaml';
        $routesWasCreatedByTest = false;

        try {
            $this->container->setParameter('kernel.project_dir', $testProjectDir);

            // Erstelle routes.yaml nur wenn sie nicht existiert
            if (!file_exists($bundleRoutesFile)) {
                $bundleConfigDir = dirname($bundleRoutesFile);
                if (!is_dir($bundleConfigDir)) {
                    mkdir($bundleConfigDir, 0755, true);
                }

                $fixtureRoutesFile = $this->fixturesDir.'/config/routes.yaml';
                copy($fixtureRoutesFile, $bundleRoutesFile);
                $routesWasCreatedByTest = true;
            }

            $this->extension->prepend($this->container);

            $routeFilePath = $testProjectDir.'/config/routes/asset_composer.yaml';
            self::assertFileExists($routeFilePath);

            $routeContent = file_get_contents($routeFilePath);
            self::assertStringContainsString('jbs_new_media_assets_composer:', $routeContent);
            self::assertStringContainsString('AssetComposerController::getAsset', $routeContent);
        } finally {
            // Cleanup
            $filesystem = new Filesystem();

            // Test-Projekt entfernen
            if (is_dir($testProjectDir)) {
                $filesystem->remove($testProjectDir);
            }

            // Bundle routes.yaml entfernen falls wir sie erstellt haben
            if ($routesWasCreatedByTest && file_exists($bundleRoutesFile)) {
                unlink($bundleRoutesFile);
            }
        }
    }

    #[Test]
    public function prependThrowsExceptionForInvalidProjectDir(): void
    {
        $this->container->setParameter('kernel.project_dir', 123);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected "kernel.project_dir" to be a string.');

        $this->extension->prepend($this->container);
    }

    #[Test]
    public function prependHandlesFilesystemException(): void
    {
        $this->container->setParameter('kernel.project_dir', '/dev/null');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('An error occurred while creating the routes file');

        $this->extension->prepend($this->container);
    }
}

final class ConfigurationTest extends TestCase
{
    #[Test]
    public function getConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        self::assertEquals('asset_composer', $treeBuilder->getRootNode()->getName());
    }

    #[Test]
    public function configurationProcessing(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, [
            [
                'paths' => ['/test/path1/', '/test/path2/'],
            ],
        ]);

        self::assertEquals(['/test/path1/', '/test/path2/'], $config['paths']);
    }

    #[Test]
    public function configurationWithEmptyArray(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, [[]]);

        self::assertArrayHasKey('paths', $config);
        self::assertEquals([], $config['paths']);
    }
}

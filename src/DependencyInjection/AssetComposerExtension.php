<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class AssetComposerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('asset_composer.paths', $config['paths'] ?? []);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        $filesystem = new Filesystem();
        $targetPath = $projectDir . '/config/routes/asset_composer.yaml';
        $sourcePath = __DIR__ . '/../../config/routes.yaml';

        try {
            $targetDir = dirname($targetPath);
            if (!$filesystem->exists($targetDir)) {
                $filesystem->mkdir($targetDir);
            }

            if (!$filesystem->exists($targetPath)) {
                $filesystem->copy($sourcePath, $targetPath);
            }
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf(
                'An error occurred while creating the routes file at %s: %s',
                $targetPath,
                $exception->getMessage()
            ));
        }
    }
}

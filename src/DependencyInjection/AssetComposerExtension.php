<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AssetComposerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('asset_composer.paths', $config['paths'] ?? []);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \InvalidArgumentException('The kernel.project_dir parameter must be a string.');
        }

        $filePath = $projectDir.'/config/routes/asset_composer.yaml';
        $bundleFile = __DIR__.'/../Resources/config/routes.yaml';
        if (!file_exists($filePath)) {
            file_put_contents($filePath, file_get_contents($bundleFile));
        }
    }
}

<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('asset_composer');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
               ->arrayNode('paths')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

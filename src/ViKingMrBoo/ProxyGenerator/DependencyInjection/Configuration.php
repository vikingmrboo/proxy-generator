<?php

namespace ViKingMrBoo\ProxyGenerator\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('viking_proxy_generator');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%/proxy')->end()
            ->scalarNode('proxies_dir')->defaultValue('%kernel.cache_dir%/proxies')->end()
            ->end();

        return $treeBuilder;
    }
}
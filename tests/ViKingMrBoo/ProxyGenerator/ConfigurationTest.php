<?php

namespace Tests\ViKingMrBoo\ProxyGenerator;

use ViKingMrBoo\ProxyGenerator\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $rootNode = $treeBuilder->buildTree();

        $this->assertEquals('viking_proxy_generator', $rootNode->getName());
        $children = $rootNode->getChildren();
        $this->assertArrayHasKey('cache_dir', $children);
        $this->assertArrayHasKey('proxies_dir', $children);
    }
}
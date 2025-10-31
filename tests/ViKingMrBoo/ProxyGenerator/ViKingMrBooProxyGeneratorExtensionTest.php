<?php

namespace Tests\ViKingMrBoo\ProxyGenerator;

use ViKingMrBoo\ProxyGenerator\DependencyInjection\ViKingMrBooProxyGeneratorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use PHPUnit\Framework\TestCase;

class ViKingMrBooProxyGeneratorExtensionTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => __DIR__ . '/../../cache',
        ]));
        $loader = new YamlFileLoader($this->container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');
    }

    public function testLoad()
    {
        $extension = new ViKingMrBooProxyGeneratorExtension();
        $extension->load([[
            'cache_dir' => '%kernel.cache_dir%/proxy',
            'proxies_dir' => '%kernel.cache_dir%/proxies',
        ]], $this->container);

        $this->assertTrue($this->container->hasDefinition('mega_coder_proxy_generator.proxy_generator_service'));
        $this->assertTrue($this->container->hasDefinition('mega_coder_proxy_generator.proxy_cache_service'));
        $this->assertTrue($this->container->hasDefinition('mega_coder_proxy_generator.proxy_integration_service'));

        $this->assertEquals('%kernel.cache_dir%/proxy', $this->container->getParameter('mega_coder_proxy_generator.cache_dir'));
        $this->assertEquals('%kernel.cache_dir%/proxies', $this->container->getParameter('mega_coder_proxy_generator.proxies_dir'));
    }
}
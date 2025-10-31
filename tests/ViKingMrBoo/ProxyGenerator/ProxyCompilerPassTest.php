<?php

namespace Tests\ViKingMrBoo\ProxyGenerator;

use ViKingMrBoo\ProxyGenerator\DependencyInjection\Compiler\ProxyCompilerPass;
use ViKingMrBoo\ProxyGenerator\ProxyGeneratorService;
use ViKingMrBoo\ProxyGenerator\ProxyCacheService;
use ViKingMrBoo\ProxyGenerator\ProxyIntegrationService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;

class ProxyCompilerPassTest extends TestCase
{
    private $containerBuilder;

    protected function setUp(): void
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->containerBuilder->setParameter('kernel.cache_dir', __DIR__ . '/../../cache');

        $proxyGeneratorServiceDefinition = new Definition(ProxyGeneratorService::class);
        $proxyCacheServiceDefinition = new Definition(ProxyCacheService::class);
        $proxyIntegrationServiceDefinition = new Definition(ProxyIntegrationService::class);

        $this->containerBuilder->setDefinition('mega_coder_proxy_generator.proxy_generator_service', $proxyGeneratorServiceDefinition);
        $this->containerBuilder->setDefinition('mega_coder_proxy_generator.proxy_cache_service', $proxyCacheServiceDefinition);
        $this->containerBuilder->setDefinition('mega_coder_proxy_generator.proxy_integration_service', $proxyIntegrationServiceDefinition);
    }

    public function testProcess()
    {
        $serviceId = 'app.client.test.test_interface';
        $interfaceName = 'App\Client\Test\TestInterface';
        $config = [
            'url' => 'https://my.test.host.com',
            'timeout' => '10s',
        ];

        $serviceDefinition = new Definition();
        $serviceDefinition->addTag('mega_coder.proxy', ['interface' => $interfaceName, 'config' => $config]);
        $this->containerBuilder->setDefinition($serviceId, $serviceDefinition);

        $compilerPass = new ProxyCompilerPass();
        $compilerPass->process($this->containerBuilder);

        $processedDefinition = $this->containerBuilder->getDefinition($serviceId);
        $className = $processedDefinition->getClass();

        $this->assertTrue(class_exists($className));
        $reflectionClass = new ReflectionClass($className);
        $methods = $reflectionClass->getMethods();

        $this->assertCount(2, $methods);
        $this->assertEquals('getInfo', $methods[0]->getName());
        $this->assertEquals('setInfo', $methods[1]->getName());
    }
}
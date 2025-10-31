<?php

namespace Tests\ViKingMrBoo\ProxyGenerator;

use ViKingMrBoo\ProxyGenerator\ProxyGeneratorService;
use ViKingMrBoo\ProxyGenerator\ProxyCacheService;
use ViKingMrBoo\ProxyGenerator\ProxyIntegrationService;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Proxy\ProxyGenerator;
use PHPUnit\Framework\TestCase;

class ProxyIntegrationServiceTest extends TestCase
{
    private $proxyGenerator;
    private $annotationReader;
    private $cache;
    private $proxyGeneratorService;
    private $proxyCacheService;
    private $proxyIntegrationService;

    protected function setUp(): void
    {
        $this->proxyGenerator = new ProxyGenerator(__DIR__ . '/proxies');
        $this->annotationReader = new AnnotationReader();
        $this->cache = new FilesystemCache(__DIR__ . '/cache');
        $this->proxyGeneratorService = new ProxyGeneratorService($this->proxyGenerator, $this->annotationReader, $this->cache);
        $this->proxyCacheService = new ProxyCacheService($this->cache);
        $this->proxyIntegrationService = new ProxyIntegrationService($this->proxyGeneratorService, $this->proxyCacheService);
    }

    protected function tearDown(): void
    {
        $this->cache->deleteAll();
        if (file_exists(__DIR__ . '/proxies')) {
            array_map('unlink', glob(__DIR__ . '/proxies/*.php'));
        }
    }

    public function testGetProxyInstance()
    {
        $interfaceName = 'App\Client\Test\TestInterface';
        $config = [
            'url' => 'https://my.test.host.com',
            'timeout' => '10s',
        ];

        $proxyClassName = $this->proxyIntegrationService->getProxyInstance($interfaceName, $config);

        $this->assertTrue(class_exists($proxyClassName));

        $reflectionClass = new ReflectionClass($proxyClassName);
        $methods = $reflectionClass->getMethods();

        $this->assertCount(2, $methods);
        $this->assertEquals('getInfo', $methods[0]->getName());
        $this->assertEquals('setInfo', $methods[1]->getName());
    }
}
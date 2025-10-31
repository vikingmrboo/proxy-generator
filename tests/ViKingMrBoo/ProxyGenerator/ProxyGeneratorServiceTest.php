<?php
namespace Tests\ViKingMrBoo\ProxyGenerator;

use ViKingMrBoo\ProxyGenerator\ProxyGeneratorService;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Proxy\ProxyGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ProxyGeneratorServiceTest extends TestCase
{
    private $proxyGenerator;
    private $annotationReader;
    private $cache;
    private $proxyGeneratorService;

    protected function setUp(): void
    {
        $this->proxyGenerator = new ProxyGenerator(__DIR__ . '/proxies');
        $this->annotationReader = new AnnotationReader();
        $this->cache = new FilesystemCache(__DIR__ . '/cache');
        $this->proxyGeneratorService = new ProxyGeneratorService($this->proxyGenerator, $this->annotationReader, $this->cache);
    }

    protected function tearDown(): void
    {
        $this->cache->deleteAll();
        if (file_exists(__DIR__ . '/proxies')) {
            array_map('unlink', glob(__DIR__ . '/proxies/*.php'));
        }
    }

    public function testGenerateProxyClass()
    {
        $interfaceName = 'App\Client\Test\TestInterface';
        $config = [
            'url' => 'https://my.test.host.com',
            'timeout' => '10s',
        ];

        $proxyClassName = $this->proxyGeneratorService->generateProxyClass($interfaceName, $config);

        $this->assertTrue(class_exists($proxyClassName));

        $reflectionClass = new ReflectionClass($proxyClassName);
        $methods = $reflectionClass->getMethods();

        $this->assertCount(2, $methods);
        $this->assertEquals('getInfo', $methods[0]->getName());
        $this->assertEquals('setInfo', $methods[1]->getName());
    }
}
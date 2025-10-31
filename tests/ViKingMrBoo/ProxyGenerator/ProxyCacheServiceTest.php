<?php

namespace Tests\ViKingMrBoo\ProxyGenerator;

use ViKingMrBoo\ProxyGenerator\ProxyCacheService;
use Doctrine\Common\Cache\FilesystemCache;
use PHPUnit\Framework\TestCase;

class ProxyCacheServiceTest extends TestCase
{
    private $cache;
    private $proxyCacheService;

    protected function setUp(): void
    {
        $this->cache = new FilesystemCache(__DIR__ . '/cache');
        $this->proxyCacheService = new ProxyCacheService($this->cache);
    }

    protected function tearDown(): void
    {
        $this->cache->deleteAll();
    }

    public function testCacheOperations()
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->assertFalse($this->proxyCacheService->contains($key));

        $this->proxyCacheService->save($key, $value);

        $this->assertTrue($this->proxyCacheService->contains($key));
        $this->assertEquals($value, $this->proxyCacheService->fetch($key));
    }
}
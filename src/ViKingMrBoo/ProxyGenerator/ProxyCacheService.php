<?php
namespace ViKingMrBoo\ProxyGenerator;

use Doctrine\Common\Cache\CacheProvider;

class ProxyCacheService
{
    private $cache;

    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    public function contains($key)
    {
        return $this->cache->contains($key);
    }

    public function fetch($key)
    {
        return $this->cache->fetch($key);
    }

    public function save($key, $value)
    {
        return $this->cache->save($key, $value);
    }
}
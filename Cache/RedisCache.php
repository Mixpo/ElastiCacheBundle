<?php

namespace Igniter\ElastiCacheBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Redis;

/**
 * Redis cache provider for ElastiCache Clusters.
 *
 * @author Jared Markell <jaredm4@gmail.com>
 */
class RedisCache extends \Doctrine\Common\Cache\RedisCache
{
    /** @var \Redis */
    private $master;
    /** @var array of \Redis */
    private $slaves = [];

    /**
     * @param \Redis $redis
     */
    public function setMaster(Redis $redis)
    {
        $redis->setOption(Redis::OPT_SERIALIZER, $this->getSerializerValue());
        $this->master = $redis;
    }

    /**
     * @param \Redis $redis
     */
    public function addSlave(Redis $redis)
    {
        $redis->setOption(Redis::OPT_SERIALIZER, $this->getSerializerValue());
        $this->slaves[] = $redis;
    }

    /**
     * @return \Redis
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * Retrieve a random slave from the list.
     * @return \Redis
     */
    public function getSlave()
    {
        return $this->slaves[array_rand($this->slaves)];
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return $this->getSlave()->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return $this->getSlave()->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            return $this->getMaster()->setex($id, $lifeTime, $data);
        }

        return $this->getMaster()->set($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->getMaster()->delete($id) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->getMaster()->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = $this->getMaster()->info();

        return array(
            Cache::STATS_HITS => false,
            Cache::STATS_MISSES => false,
            Cache::STATS_UPTIME => $info['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE => $info['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE => false
        );
    }
}

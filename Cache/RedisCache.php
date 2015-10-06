<?php

namespace Igniter\ElastiCacheBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Psr\Log\LoggerInterface;
use Redis;

/**
 * Redis cache provider for ElastiCache Clusters.
 *
 * @author Jared Markell <jaredm4@gmail.com>
 */
class RedisCache extends \Doctrine\Common\Cache\RedisCache
{
    /** @var array Host, port and timeout for connecting to Redis write server. */
    private $writeConfig = [];
    /** @var array[] Host, port and timeout for connecting to Redis write server. */
    private $readConfigs = [];
    /** @var Redis */
    private $write;
    /** @var Redis */
    private $read;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param Redis $redis
     * @param $host
     * @param $port
     * @param int $timeout
     */
    public function addRead(Redis $redis, $host, $port, $timeout = 1)
    {
        $this->readConfigs[] = [
            'redis' => $redis,
            'host' => $host,
            'port' => $port,
            'timeout' => $timeout,
        ];
    }

    /**
     * @param Redis $redis
     * @param $host
     * @param $port
     * @param int $timeout
     */
    public function setWrite(Redis $redis, $host, $port, $timeout = 1)
    {
        $this->writeConfig = [
            'redis' => $redis,
            'host' => $host,
            'port' => $port,
            'timeout' => $timeout,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return $this->getRead()->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return $this->getRead()->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            return $this->getWrite()->setex($id, $lifeTime, $data);
        }

        return $this->getWrite()->set($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->getWrite()->delete($id) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->getWrite()->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = $this->getWrite()->info();

        return array(
            Cache::STATS_HITS => false,
            Cache::STATS_MISSES => false,
            Cache::STATS_UPTIME => $info['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE => $info['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE => false
        );
    }

    /**
     * Retrieve a random read replica from the list.
     * @return \Redis
     * @throws \RedisException
     */
    private function getRead()
    {
        if (!$this->read) {
            shuffle($this->readConfigs);
            foreach ($this->readConfigs as $config) {
                /** @var \Redis $redis */
                $redis = $config['redis'];
                try {
                    $redis->connect($config['host'], $config['port'], $config['timeout']);
                    $redis->setOption(Redis::OPT_SERIALIZER, $this->getSerializerValue());
                    $this->read = $redis;
                    break;
                }
                catch (\RedisException $ex) {
                    if ($this->logger) {
                        $this->logger->warning('Failed to connect to a Redis read replica.', [
                            'exception' => $ex,
                            'host' => $config['host'],
                            'port' => $config['port'],
                        ]);
                    }
                }
            }

            // If we failed to connect to any read replicas, give up, and send back the last exception (if any)
            if (!$this->read) {
                throw new \RedisException('Failed to connect to any Redis read replicas.', 0, isset($ex) ? $ex : null);
            }
        }

        return $this->read;
    }

    /**
     * @return \Redis
     */
    private function getWrite()
    {
        if (!$this->write) {
            $this->write = $this->writeConfig['redis'];
            $this->write->connect($this->writeConfig['host'], $this->writeConfig['port'], $this->writeConfig['timeout']);
            $this->write->setOption(Redis::OPT_SERIALIZER, $this->getSerializerValue());
        }

        return $this->write;
    }
}

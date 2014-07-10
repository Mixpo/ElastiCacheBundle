<?php

namespace Igniter\ElastiCacheBundle\Tests\Client;

use Doctrine\Common\Cache\Cache;
use Igniter\ElastiCacheBundle\Cache\RedisCache;

class RedisCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Redis */
    private $node1Mock;
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Redis */
    private $node2Mock;

    private $namespace = 'baz';

    protected function setUp()
    {
        $this->node1Mock = $this->getMock('Redis');
        $this->node2Mock = $this->getMock('Redis');
    }

    protected function tearDown()
    {
        $this->node1Mock = null;
        $this->node2Mock = null;
    }

    public function testSetMaster()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));

        $redis = new RedisCache();
        $redis->setMaster($this->node1Mock);

        $this->assertEquals($this->node1Mock, $redis->getMaster());
    }

    public function testAddSlave()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));

        $redis = new RedisCache();
        $redis->addSlave($this->node1Mock);

        $this->assertEquals($this->node1Mock, $redis->getSlave());
    }

    public function testGetSlave()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));

        $redis = new RedisCache();
        $redis->addSlave($this->node1Mock);

        // todo maybe should flush out how to ensure we get a random slave?
        $this->assertEquals($this->node1Mock, $redis->getSlave());
    }

    public function testFetch()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));

        $this->node1Mock->expects($this->at(1))
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->will($this->returnValue(1));
        $this->node1Mock->expects($this->at(2))
            ->method('get')
            ->with("{$this->namespace}[foo][1]")
            ->will($this->returnValue('bar'));

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->addSlave($this->node1Mock);

        $this->assertEquals('bar', $redis->fetch('foo'));
    }

    public function testContains()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));

        $this->node1Mock->expects($this->at(1))
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->will($this->returnValue(1));
        $this->node1Mock->expects($this->at(2))
            ->method('exists')
            ->with("{$this->namespace}[foo][1]")
            ->will($this->returnValue(true));

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->addSlave($this->node1Mock);

        $this->assertTrue($redis->contains('foo'));
    }

    public function testSave()
    {
        // slave
        $this->node2Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node2Mock->expects($this->at(1))
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->will($this->returnValue(1));
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->at(1))
            ->method('set')
            ->with("{$this->namespace}[foo][1]", 'bam')
            ->will($this->returnValue(true));

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setMaster($this->node1Mock);
        $redis->addSlave($this->node2Mock);

        $this->assertTrue($redis->save('foo', 'bam'));
    }

    public function testSaveTtl()
    {
        // slave
        $this->node2Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node2Mock->expects($this->at(1))
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->will($this->returnValue(1));
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->at(1))
            ->method('setex')
            ->with("{$this->namespace}[foo][1]", 1, 'bam')
            ->will($this->returnValue(true));

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setMaster($this->node1Mock);
        $redis->addSlave($this->node2Mock);

        $this->assertTrue($redis->save('foo', 'bam', 1));
    }

    public function testDelete()
    {
        // slave
        $this->node2Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node2Mock->expects($this->at(1))
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->will($this->returnValue(1));
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->at(1))
            ->method('delete')
            ->with("{$this->namespace}[foo][1]")
            ->will($this->returnValue(1));

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setMaster($this->node1Mock);
        $redis->addSlave($this->node2Mock);

        $this->assertTrue($redis->delete('foo'));
    }

    public function testFlush()
    {
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->at(1))
            ->method('flushDB')
            ->will($this->returnValue(true));

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setMaster($this->node1Mock);

        $this->assertTrue($redis->flushAll());
    }

    public function testGetStats()
    {
        $redis_info = [
            'uptime_in_seconds' => 1,
            'used_memory' => 1,
        ];
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->at(1))
            ->method('info')
            ->will($this->returnValue($redis_info));

        $redis = new RedisCache();
        $redis->setMaster($this->node1Mock);

        $actual = $redis->getStats();
        $this->assertInternalType('array', $actual);
        $this->assertFalse($actual[Cache::STATS_HITS]);
        $this->assertFalse($actual[Cache::STATS_MISSES]);
        $this->assertEquals($redis_info['uptime_in_seconds'], $actual[Cache::STATS_UPTIME]);
        $this->assertEquals($redis_info['used_memory'], $actual[Cache::STATS_MEMORY_USAGE]);
        $this->assertFalse($actual[Cache::STATS_MEMORY_AVAILABLE]);
    }
}

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
        $this->node1Mock = $this->getMockBuilder('Redis')
            ->setMethods(['setOption', 'connect', 'get', 'set', 'setex', 'exists', 'delete', 'info', 'flushDB'])
            ->getMock();
        $this->node2Mock = $this->getMock('Redis');
    }

    public function testSetWrite()
    {
        $this->node1Mock->expects($this->never())
            ->method('setOption');
        $this->node1Mock->expects($this->never())
            ->method('connect');

        $redis = new RedisCache();
        $redis->setWrite($this->node1Mock, 'localhost', 1234);
    }
    public function testAddRead()
    {
        $this->node1Mock->expects($this->never())
            ->method('setOption');
        $this->node1Mock->expects($this->never())
            ->method('connect');

        $redis = new RedisCache();
        $redis->addRead($this->node1Mock, 'localhost', 1234);
    }
    public function testGetRead()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->once())
            ->method('connect')
            ->with('localhost', 1234, 0.0);

        // make private public
        $method = new \ReflectionMethod('Igniter\ElastiCacheBundle\Cache\RedisCache', 'getRead');
        $method->setAccessible(true);

        $redis = new RedisCache();
        $redis->addRead($this->node1Mock, 'localhost', 1234);

        $this->assertEquals($this->node1Mock, $method->invoke($redis));
    }
    public function testGetReadWithTimeout()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->once())
            ->method('connect')
            ->with('localhost', 1234, 4);

        // make private public
        $method = new \ReflectionMethod('Igniter\ElastiCacheBundle\Cache\RedisCache', 'getRead');
        $method->setAccessible(true);

        $redis = new RedisCache();
        $redis->addRead($this->node1Mock, 'localhost', 1234, 4);

        $this->assertEquals($this->node1Mock, $method->invoke($redis));
    }
    public function testGetReadFromMultiple()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->once())
            ->method('connect');

        // make private public
        $method = new \ReflectionMethod('Igniter\ElastiCacheBundle\Cache\RedisCache', 'getRead');
        $method->setAccessible(true);

        $redis = new RedisCache();
        $redis->addRead($this->node1Mock, 'localhost', 1234);
        $redis->addRead($this->node1Mock, 'localhost', 12345);

        $this->assertEquals($this->node1Mock, $method->invoke($redis));
    }
    public function testGetReadAlwaysSucceedWhenOneFailsToConnect()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->exactly(2))
            ->method('connect')
            ->withConsecutive($this->logicalOr(
                ['localhost', 1234, 0.0],
                ['localhost', 12345, 0.0]
            ))
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \RedisException('Test exception')),
                true
            );

        // make private public
        $method = new \ReflectionMethod('Igniter\ElastiCacheBundle\Cache\RedisCache', 'getRead');
        $method->setAccessible(true);

        $redis = new RedisCache();
        $redis->addRead($this->node1Mock, 'localhost', 1234);
        $redis->addRead($this->node1Mock, 'localhost', 12345);

        $method->invoke($redis);
        $this->assertEquals($this->node1Mock, $method->invoke($redis));
    }
    public function testGetReadLogsWhenUnableToConnect()
    {
        $this->node1Mock->expects($this->never())
            ->method('setOption');
        $this->node1Mock->expects($this->once())
            ->method('connect')
            ->with('localhost', 1234, 0.0)
            ->willThrowException(new \RedisException('Test exception'));
        $logger = $this->getMock('Psr\Log\NullLogger');
        $logger->expects($this->once())
            ->method('warning');

        // make private public
        $method = new \ReflectionMethod('Igniter\ElastiCacheBundle\Cache\RedisCache', 'getRead');
        $method->setAccessible(true);

        $redis = new RedisCache($logger);
        $redis->addRead($this->node1Mock, 'localhost', 1234);

        $this->setExpectedException('RedisException');
        $method->invoke($redis);
    }
    public function testGetReadNoServersExist()
    {
        // make private public
        $method = new \ReflectionMethod('Igniter\ElastiCacheBundle\Cache\RedisCache', 'getRead');
        $method->setAccessible(true);

        $redis = new RedisCache();

        $this->setExpectedException('RedisException');
        $method->invoke($redis);
    }
    public function testGetReadThrowWhenFailedToConnect()
    {
        $this->node1Mock->expects($this->never())
            ->method('setOption');
        $this->node1Mock->expects($this->once())
            ->method('connect')
            ->willThrowException(new \RedisException('Test exception'));

        // make private public
        $method = new \ReflectionMethod('Igniter\ElastiCacheBundle\Cache\RedisCache', 'getRead');
        $method->setAccessible(true);

        $redis = new RedisCache();
        $redis->addRead($this->node1Mock, 'localhost', 1234);

        $this->setExpectedException('RedisException', 'Failed to connect to any Redis read replicas.');
        $method->invoke($redis);
    }
    public function testFetch()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ["DoctrineNamespaceCacheKey[{$this->namespace}]"],
                ["{$this->namespace}[foo][1]"]
            )
            ->willReturnOnConsecutiveCalls(
                1,
                'bar'
            );

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->addRead($this->node1Mock, 'localhost', 1234);

        $this->assertEquals('bar', $redis->fetch('foo'));
    }
    public function testContains()
    {
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));

        $this->node1Mock->expects($this->once())
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->willReturn(1);
        $this->node1Mock->expects($this->once())
            ->method('exists')
            ->with("{$this->namespace}[foo][1]")
            ->will($this->returnValue(true));

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->addRead($this->node1Mock, 'localhost', 1234);

        $this->assertTrue($redis->contains('foo'));
    }
    public function testSave()
    {
        // slave
        $this->node2Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node2Mock->expects($this->once())
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->willReturn(1);
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->once())
            ->method('set')
            ->with("{$this->namespace}[foo][1]", 'bam')
            ->willReturn(true);

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setWrite($this->node1Mock, 'localhost', 1234);
        $redis->addRead($this->node2Mock, 'localhost', 12345);

        $this->assertTrue($redis->save('foo', 'bam'));
    }
    public function testSaveTtl()
    {
        // slave
        $this->node2Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node2Mock->expects($this->once())
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->will($this->returnValue(1));
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->once())
            ->method('setex')
            ->with("{$this->namespace}[foo][1]", 1, 'bam')
            ->willReturn(true);

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setWrite($this->node1Mock, 'localhost', 1234);
        $redis->addRead($this->node2Mock, 'localhost', 12345);

        $this->assertTrue($redis->save('foo', 'bam', 1));
    }
    public function testDelete()
    {
        // slave
        $this->node2Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node2Mock->expects($this->once())
            ->method('get')
            ->with("DoctrineNamespaceCacheKey[{$this->namespace}]")
            ->willReturn(1);
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->once())
            ->method('delete')
            ->with("{$this->namespace}[foo][1]")
            ->willReturn(true);

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setWrite($this->node1Mock, 'localhost', 1234);
        $redis->addRead($this->node2Mock, 'localhost', 12345);

        $this->assertTrue($redis->delete('foo'));
    }
    public function testFlush()
    {
        // master
        $this->node1Mock->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, $this->isType('int'));
        $this->node1Mock->expects($this->once())
            ->method('flushDB')
            ->willReturn(true);

        $redis = new RedisCache();
        $redis->setNamespace($this->namespace);
        $redis->setWrite($this->node1Mock, 'localhost', 1234);

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
        $this->node1Mock->expects($this->once())
            ->method('info')
            ->willReturn($redis_info);

        $redis = new RedisCache();
        $redis->setWrite($this->node1Mock, 'localhost', 1234);

        $actual = $redis->getStats();
        $this->assertInternalType('array', $actual);
        $this->assertFalse($actual[Cache::STATS_HITS]);
        $this->assertFalse($actual[Cache::STATS_MISSES]);
        $this->assertEquals($redis_info['uptime_in_seconds'], $actual[Cache::STATS_UPTIME]);
        $this->assertEquals($redis_info['used_memory'], $actual[Cache::STATS_MEMORY_USAGE]);
        $this->assertFalse($actual[Cache::STATS_MEMORY_AVAILABLE]);
    }
}

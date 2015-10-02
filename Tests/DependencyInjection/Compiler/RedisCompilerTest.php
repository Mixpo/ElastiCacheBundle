<?php

namespace Igniter\ElastiCacheBundle\Tests\DependencyInjection;

use Igniter\ElastiCacheBundle\DependencyInjection\Compiler\RedisCompiler;
use Symfony\Component\DependencyInjection\Definition;

class RedisCompilerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder */
    private $containerMock;
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\Definition */
    private $definitionMock;
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag */
    private $paramterBagMock;

    protected function setUp()
    {
        $this->containerMock = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->definitionMock = $this->getMock('Symfony\Component\DependencyInjection\Definition');
        $this->paramterBagMock = $this->getMock('Symfony\Component\DependencyInjection\ParameterBag\ParameterBag');
    }

    public function testProcess()
    {
        $servers = [
            ['host'=>'127.0.0.1', 'port'=>1234, 'timeout'=>1],
            ['host'=>'localhost', 'port'=>12345, 'master'=>true],
        ];

        $this->containerMock->expects($this->at(0))
            ->method('hasParameter')
            ->with('cache.redis.servers')
            ->will($this->returnValue(true));
        $this->containerMock->expects($this->at(1))
            ->method('getDefinition')
            ->with('igniter.elasticache.rediscache')
            ->will($this->returnValue($this->definitionMock));
        $this->containerMock->expects($this->at(2))
            ->method('getParameterBag')
            ->will($this->returnValue($this->paramterBagMock));
        $this->containerMock->expects($this->at(3))
            ->method('getParameter')
            ->with('igniter.elasticache.redis_class')
            ->will($this->returnValue('Redis'));
        $this->containerMock->expects($this->at(4))
            ->method('getParameter')
            ->with('cache.redis.servers')
            ->will($this->returnValue($servers));
        // look up redis class param
        $this->paramterBagMock->expects($this->at(0))
            ->method('resolveValue')
            ->will($this->returnValue('Redis'));
        // look up servers param
        $this->paramterBagMock->expects($this->at(1))
            ->method('resolveValue')
            ->will($this->returnValue($servers));
        // setMaster and addSlave calls
        $this->definitionMock->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addRead', [new Definition('Redis'), '127.0.0.1', 1234, 1]],
                ['setWrite', [new Definition('Redis'), 'localhost', 12345, 0.0]]
            );

        $compiler = new RedisCompiler();
        $compiler->process($this->containerMock);
    }

    public function testProcessUndefinedServers()
    {
        $this->containerMock->expects($this->at(0))
            ->method('hasParameter')
            ->with('cache.redis.servers')
            ->will($this->returnValue(false));
        $this->containerMock->expects($this->never())
            ->method('getParameterBag');

        $compiler = new RedisCompiler();
        $compiler->process($this->containerMock);
    }
}

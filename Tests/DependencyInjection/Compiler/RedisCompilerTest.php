<?php

namespace Igniter\ElastiCacheBundle\Tests\DependencyInjection;

use Igniter\ElastiCacheBundle\DependencyInjection\Compiler\RedisCompiler;

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

    protected function tearDown()
    {
        unset($this->containerMock);
        unset($this->definitionMock);
        unset($this->paramterBagMock);
    }

    public function testProcess()
    {
        $servers = [
            ['host'=>'127.0.0.1', 'port'=>6379],
            ['host'=>'localhost', 'port'=>6380, 'master'=>true],
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
        $this->definitionMock->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addSlave', $this->callback(function ($subject) {
                // verify the subject is an array of 1 Defintion class
                if (count($subject) <> 1 && !is_a($subject[0], 'Symfony\Component\DependencyInjection\Definition')) {
                    return false;
                }
                /** @var $definition \Symfony\Component\DependencyInjection\Definition */
                $definition = $subject[0];
                // verify the connect call
                if ($definition->getMethodCalls() !== [['connect', ['127.0.0.1', 6379]]]) {
                    return false;
                }
                return true;
            }));
        $this->definitionMock->expects($this->at(1))
            ->method('addMethodCall')
            ->with('setMaster', $this->callback(function ($subject) {
                // verify the subject is an array of 1 Defintion class
                if (count($subject) <> 1 && !is_a($subject[0], 'Symfony\Component\DependencyInjection\Definition')) {
                    return false;
                }
                /** @var $definition \Symfony\Component\DependencyInjection\Definition */
                $definition = $subject[0];
                // verify the connect call
                if ($definition->getMethodCalls() !== [['connect', ['localhost', 6380]]]) {
                    return false;
                }
                return true;
            }));

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

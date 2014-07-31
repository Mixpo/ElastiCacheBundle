<?php

namespace Igniter\ElastiCacheBundle\Tests\DependencyInjection\Definition;

use Igniter\ElastiCacheBundle\DependencyInjection\Definition\RedisCacheDefinition;

class RedisCacheDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder */
    private $containerMock;
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\Definition */
    private $definitionMock;

    protected function setUp()
    {
        $this->containerMock = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->definitionMock = $this->getMock('Symfony\Component\DependencyInjection\Definition');
    }

    protected function tearDown()
    {
        unset($this->containerMock);
        unset($this->definitionMock);
    }

    public function testConfigure()
    {
        $config = [
            'type' => 'custom_provider',
            'custom_provider' => [
                'type' => 'igniter.elasticache',
                'options' => [
                    'namespace' => 'foo'
                ],
            ],
        ];
        $this->definitionMock->expects($this->once())
            ->method('addMethodCall')
            ->with('setNamespace', ['foo']);

        $definition = new RedisCacheDefinition('igniter.elasticache.rediscache');
        $definition->configure('foo.service', $config, $this->definitionMock, $this->containerMock);
    }
}

<?php

namespace Igniter\ElastiCacheBundle\Tests;

use Igniter\ElastiCacheBundle\IgniterElastiCacheBundle;

class IgniterElastiCacheBundleTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder */
    private $containerMock;

    protected function setUp()
    {
        $this->containerMock = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
    }

    protected function tearDown()
    {
        unset($this->containerMock);
    }

    public function testBuild()
    {
        $this->containerMock->expects($this->once())
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Igniter\ElastiCacheBundle\DependencyInjection\Compiler\RedisCompiler'));

        $compiler = new IgniterElastiCacheBundle();
        $compiler->build($this->containerMock);
    }
}

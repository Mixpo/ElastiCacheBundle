<?php

namespace Igniter\ElastiCacheBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Igniter\ElastiCacheBundle\DependencyInjection\Compiler\RedisCompiler;

/**
 * Bundle for support ElastiCache Clusters in Symfony.
 *
 * @author Jared Markell <jaredm4@gmail.com>
 */
class IgniterElastiCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RedisCompiler());
    }
}

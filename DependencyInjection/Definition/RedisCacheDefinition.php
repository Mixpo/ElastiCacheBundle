<?php

namespace Igniter\ElastiCacheBundle\DependencyInjection\Definition;

use Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection\Definition\CacheDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Redis Cache Definition
 *
 * @author Jared Markell <jaredm4@gmail.com>
 */
class RedisCacheDefinition extends CacheDefinition
{
    /**
     * Allow setting of Namespaces.
     * {@inheritdoc}
     */
    public function configure($name, array $config, Definition $service, ContainerBuilder $container)
    {
        $options = $config['custom_provider']['options'];
        if (isset($options['namespace'])) {
            $service->addMethodCall('setNamespace', [$options['namespace']]);
        }
    }
}

<?php

namespace Igniter\ElastiCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Initializes RedisCache service with Redis Servers if they are defined.
 *
 * @author Jared Markell <jaredm4@gmail.com>
 */
class RedisCompiler implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // If no Redis servers are defined, don't do anything
        if (!$container->hasParameter('cache.redis.servers')) {
            return;
        }

        // Grab the RedisCache provider
        $definition = $container->getDefinition('igniter.elasticache.rediscache');

        $parameterBag = $container->getParameterBag();

        // The class each Redis is based on
        $redis_classname = $parameterBag->resolveValue($container->getParameter('igniter.elasticache.redis_class'));

        // The defined Redis servers
        $servers = $parameterBag->resolveValue($container->getParameter('cache.redis.servers'));
        foreach ($servers as $server) {
            // Create a definition for each Redis object
            $redis_definition = new Definition($redis_classname);

            // Add to the RedisCache provider
            if (isset($server['master']) && $server['master']) {
                $definition->addMethodCall('setWrite', [$redis_definition, $server['host'], $server['port'], isset($server['timeout']) ? $server['timeout'] : 0.0]);
            } else {
                $definition->addMethodCall('addRead', [$redis_definition, $server['host'], $server['port'], isset($server['timeout']) ? $server['timeout'] : 0.0]);
            }
        }
    }
}

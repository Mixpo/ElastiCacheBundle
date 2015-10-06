ElastiCacheBundle
=================

An ElastiCache Bundle for Symfony. This could also be used for Redis Clusters that aren't in ElastiCache as well. To that end, we use typical "master" and "slave" nomenclature instead of ElastiCache's "primary" and "read" node names.

[![Codeship Status for ShopIgniter/ElastiCacheBundle](https://codeship.io/projects/fba198c0-f3ed-0131-a47e-6a1bcd925291/status?branch=master)](https://codeship.io/projects/27992)
[![Coverage Status](https://coveralls.io/repos/Mixpo/ElastiCacheBundle/badge.svg?branch=master&service=github)](https://coveralls.io/github/Mixpo/ElastiCacheBundle?branch=master)

## Installation

To enable the RedisCache service, add your servers to your parameters.yml.
```
parameters:
    # ...
    cache.redis.servers:
        - { host: primary-write.ng.amazonaws.example.com, port: 6379, master: true, timeout: 5 }
        - { host: primary-read.amazonaws.example.com, port: 6379, timeout: 5 }
        - { host: read-1.amazonaws.example.com, port: 6379, timeout: 5 }
```

### Notes

The Master host and port come from ElastiCache's Replication Group [Primary Endpoint](http://docs.aws.amazon.com/AmazonElastiCache/latest/UserGuide/Replication.html#Replication.PrimaryEndpoint). Do not use the node's endpoint for writing. Likewise, do not use the Replication Group's Primary Endpoint as a read server. Instead use the primary node's endpoint for reading.

## Usage

To use directly, grab the service from the container.
```
/** @var \Igniter\ElastiCacheBundle\Cache\RedisCache $cache */
$cache = $this->get('igniter.elasticache.rediscache');
$bar = $cache->fetch('foo');
// ...
$cache->save('foo', $bar);
```

To use as a Doctrine Custom Cache Provider, use the following in your config. Using aliases, you can also retrieve the service by this alias out of the container.
```
doctrine_cache:
    aliases:
        cache: my_provider
    custom_providers:
        igniter.elasticache:
            prototype:  "igniter.elasticache.rediscache"
            definition_class: "Igniter\ElastiCacheBundle\DependencyInjection\Definition\RedisCacheDefinition"
    providers:
        my_provider:
            igniter.elasticache:
                namespace: "foo"
```

## ToDo

* Support Memcached (with or without AWS' [ElastiCache Cluster Client for PHP](http://docs.aws.amazon.com/AmazonElastiCache/latest/UserGuide/Appendix.PHPAutoDiscoverySetup.html)).
* Configuration validation.

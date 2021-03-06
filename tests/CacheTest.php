<?php

use Nano\Cache;

/**
 * Generic class for unit tests for Cache drivers
 */
abstract class CacheTest extends \Nano\NanoBaseTest
{
    protected function getCache($driver, array $settings = [])
    {
        // use test application's directory
        $dir = realpath(dirname(__FILE__) . '/app');
        $app = Nano::app($dir);

        $settings = array_merge([
            'driver' => $driver,
            'password' => getenv('REDIS_PASSWORD')
        ], $settings);

        return Cache::factory($settings);
    }

    public function testCacheFactory()
    {
        $this->assertInstanceOf('Nano\Cache\CacheFile', $this->getCache('file'));
        $this->assertInstanceOf('Nano\Cache\CacheFile', $this->getCache('File'));
        $this->assertInstanceOf('Nano\Cache\CacheRedis', $this->getCache('redis', ['host' => '127.0.0.1']));
    }

    /**
     * extend this method to run the following tests
     *
     * @param array $settings
     * @return Cache
     */
    abstract protected function getCacheInstance($settings = []);

    public function testCacheGetSet()
    {
        $cache = $this->getCacheInstance();
        if ($cache === false) {
            return;
        }

        $key = 'foo';
        $value = [
            'test' => true,
            'mixed' => [1,2,3],
            'pi' => 3.1415
        ];

        $this->assertTrue($cache->set($key, $value, 60));
        $this->assertTrue($cache->exists($key));
        $this->assertEquals($value, $cache->get($key));

        $cache->delete($key);

        $this->assertFalse($cache->exists($key));
        $this->assertNull($cache->get($key));

        // mixed keys
        $key = ['foo', 'bar', 'test'];

        $this->assertTrue($cache->set($key, $value, 60));
        $this->assertTrue($cache->exists($key));
        $this->assertEquals($value, $cache->get($key));

        $cache->delete($key);

        $this->assertFalse($cache->exists($key));
        $this->assertNull($cache->get($key));

        // handling of non-existing keys
        $key = 'notExistingKey';

        $this->assertFalse($cache->exists($key));
        $this->assertNull($cache->get($key));
        $this->assertEquals($value, $cache->get($key, $value));
    }

    public function testCacheIncrDecr()
    {
        $cache = $this->getCacheInstance();
        if ($cache === false) {
            return;
        }

        $key = 'bar';
        $value = 12;

        $cache->delete($key);
        $this->assertFalse($cache->exists($key));
        $this->assertEquals(1, $cache->incr($key));

        $cache->set($key, $value, 60);

        $this->assertEquals($value, $cache->get($key));
        $this->assertEquals($value + 1, $cache->incr($key));
        $this->assertEquals($value + 5, $cache->incr($key, 4));
        $this->assertEquals($value + 5, $cache->get($key));

        $this->assertEquals($value + 4, $cache->decr($key));
        $this->assertEquals($value, $cache->decr($key, 4));
        $this->assertEquals($value, $cache->get($key));

        $cache->delete($key);

        $this->assertFalse($cache->exists($key));
    }

    public function testCachePrefix()
    {
        $cacheA = $this->getCacheInstance();
        $cacheB = $this->getCacheInstance(['prefix' => 'foo']);

        if ($cacheA === false) {
            return;
        }

        $key = 'bar';
        $value = 12;

        $cacheA->set($key, $value, 60);
        $cacheA->delete('test');

        $this->assertTrue($cacheA->exists($key));
        $this->assertFalse($cacheB->exists($key));

        $cacheB->set('test', $value, 60);

        $this->assertFalse($cacheA->exists('test'));
        $this->assertTrue($cacheB->exists('test'));

        $cacheA->delete($key);
        $cacheB->delete('test');

        $this->assertFalse($cacheA->exists($key));
        $this->assertFalse($cacheB->exists('test'));
    }
}

<?php

use Nano\Cache;
use Nano\NanoBaseTest;

/**
 * Generic class for unit tests for Cache drivers
 */
abstract class CacheTestBase extends NanoBaseTest
{
    /**
     * @throws Exception
     */
    protected function getCache($driver, array $settings = []): Cache
    {
        // use test application's directory
        $dir = realpath(__DIR__ . '/app');
        $app = Nano::app($dir);

        $settings = array_merge([
            'driver' => $driver,
            'password' => getenv('REDIS_PASSWORD'),
        ], $settings);

        return Cache::factory($settings);
    }

    /**
     * @param string $cacheDriver
     * @param array $cacheOptions
     * @param string $expectedClass
     * @dataProvider cacheFactoryProvider
     * @throws Exception
     */
    public function testCacheFactory(string $cacheDriver, array $cacheOptions, string $expectedClass)
    {
        $this->assertInstanceOf($expectedClass, $this->getCache($cacheDriver, $cacheOptions));
    }

    static public function cacheFactoryProvider(): Generator
    {
        yield 'file' => [
            'file', [], Cache\CacheFile::class,
        ];
        yield 'File' => [
            'File', [], Cache\CacheFile::class,
        ];
        yield 'redis' => [
            'redis', ['host' => '127.0.0.1'], Cache\CacheRedis::class,
        ];
    }

    /**
     * extend this method to run the following tests
     *
     * @param array $settings
     * @return Cache
     */
    abstract protected function getCacheInstance(array $settings = []): Cache;

    public function testCacheGetSet()
    {
        $cache = $this->getCacheInstance();

        $key = 'foo';
        $value = [
            'test' => true,
            'mixed' => [1,2,3],
            'pi' => 3.1415,
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

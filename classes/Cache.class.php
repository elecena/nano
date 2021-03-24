<?php

namespace Nano;

use Nano\Logger\NanoLogger;

/**
 * Abstract class for caching driver
 */
abstract class Cache
{

    // key parts separator
    const SEPARATOR = '::';

    // debug
    protected $debug;
    protected $logger;

    // prefix for key names
    protected $prefix;

    // number of hits for cache keys
    protected $hits = 0;

    // number of misses for cache keys
    protected $misses = 0;

    /**
     * Creates an instance of given cache driver
     *
     * @param array $settings
     * @return Cache cache instance
     * @throws \Exception
     */
    public static function factory(array $settings)
    {
        $driver = isset($settings['driver']) ? $settings['driver'] : null;
        $className = sprintf('Nano\\Cache\\Cache%s', ucfirst($driver));

        try {
            return new $className($settings);
        } catch (\Exception $e) {
            NanoLogger::getLogger('nano.cache')->error($e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * @param array $settings
     * @throws \Exception
     */
    protected function __construct(array $settings)
    {
        // use debugger from the application
        $app = \NanoApp::app();
        $driver = $settings['driver'];

        $this->debug = $app->getDebug();

        $this->logger = NanoLogger::getLogger("nano.cache.{$driver}");

        // add performance report
        $events = $app->getEvents();
        $events->bind('NanoAppTearDown', [$this, 'onNanoAppTearDown']);

        // set prefix
        $this->prefix = isset($settings['prefix']) ? $settings['prefix'] : false;
    }

    /**
     * Get the data from a given cache key.
     *
     * On cache miss generate new data using provided callback
     *
     * @param string|array $key
     * @param callable $callback
     * @param $ttl
     * @return mixed
     */
    public function cache($key, callable $callback, $ttl)
    {
        $data = $this->get($key, null);

        // regenerate data on cache miss
        if (is_null($data)) {
            $this->set($key, $data = $callback(), $ttl);
        }

        return $data;
    }

    /**
     * Gets key value
     */
    abstract public function get($key, $default = null);

    /**
     * Sets key value
     */
    abstract public function set($key, $value, $ttl = null);

    /**
     * Checks if given key exists
     */
    abstract public function exists($key);

    /**
     * Deletes given key
     */
    abstract public function delete($key);

    /**
     * Increases given key's value and returns updated value
     */
    abstract public function incr($key, $by = 1);

    /**
     * Decreases given key's value and returns updated value
     */
    abstract public function decr($key, $by = 1);

    /**
     * Serialize data before storing in the cache
     */
    protected function serialize($data)
    {
        return json_encode($data);
    }

    /**
     * Unserialize data after returning them from the cache
     */
    protected function unserialize($data)
    {
        return json_decode($data, true /* as array */);
    }

    /**
     * Get key used for storing in the cache
     *
     * @param string|array $key
     * @return string
     */
    protected function getStorageKey($key)
    {
        // merge key passed as an array
        if (is_array($key)) {
            $key = implode(self::SEPARATOR, $key);
        }

        // add prefix (if provided)
        if ($this->prefix !== false) {
            $key = $this->prefix . self::SEPARATOR . $key;
        }

        return str_replace(' ', '_', $key);
    }

    /**
     * Get number of cache hits
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Get number of cache misses
     */
    public function getMisses()
    {
        return $this->misses;
    }

    /**
     * Add performance report to the log
     */
    public function onNanoAppTearDown(\NanoApp $app)
    {
        $debug = $app->getDebug();
        $request = $app->getRequest();

        // don't report stats for command line script
        if ($request->isCLI()) {
            return;
        }

        $debug->log("Cache: {$this->hits} hits and {$this->misses} misses");
        $this->logger->info('Performance data', [
            'hits' => $this->getHits(),
            'misses' => $this->getMisses()
        ]);
    }
}

<?php

/**
 * Common class for Static assets processors
 *
 * $Id$
 */
abstract class StaticAssetsProcessor {

	const CACHE_TTL = 86400;

	protected $app;
	protected $cache;
	protected $cb;
	protected $staticAssets;
	protected $type;

	public function __construct(NanoApp $app, StaticAssets $staticAssets, $type) {
		$this->app = $app;
		$this->staticAssets = $staticAssets;

		$this->cache = $this->app->getCache();
		$this->cb = $this->staticAssets->getCacheBuster();
		$this->type = $type;
	}

	protected function inDebugMode() {
		return $this->staticAssets->inDebugMode();
	}

	/**
	 * Get cache key for given files
	 *
	 * Includes cache buster value in the key
	 */
	protected function getCacheKey($files) {
		return "static-{$this->type}-r{$this->cb}-" . sha1(serialize($files));
	}

	/**
	 * Process given files with caching functionality
	 */
	public function processFiles(Array $files) {
		$key = $this->getCacheKey($files);
		$ret = $this->cache->get($key);

		// don't cache in debug mode
		if ($this->inDebugMode()) {
			$ret = $this->process($files);
		}
		else {
			if (!is_string($ret)) {
				$ret = $this->process($files);

				$ret .= "\n\n/* Cached as {$key} */";
				$this->cache->set($key, $ret, self::CACHE_TTL);
			}
			else {
				$this->app->getDebug()->log(__METHOD__. ': cache hit');
			}
		}

		return $ret;
	}

	abstract protected function process(Array $files);
}
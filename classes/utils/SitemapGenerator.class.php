<?php

/**
 * Helper class for generating XML sitemaps using XMLWriter
 *
 * @see http://www.sitemaps.org/pl/protocol.html
 *
 * $Id$
 */

class SitemapGenerator {

	const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
	const URLS_PER_FILE = 30000;

	private $app;
	private $debug;
	private $dir;
	private $router;

	// list of sitemap's URLs
	private $urls;
	private $urlsCount = 0;

	// list of sitemaps to be stored in sitemapindex file
	private $sitemaps = array();

	function __construct(NanoApp $app) {
		$this->app = $app;

		$this->debug = $app->getDebug();
		$this->dir = $app->getDirectory();
		$this->router = $app->getRouter();
	}

	/**
	 * Helper metod constructing XMLWriter document
	 *
	 * @see http://php.net/XMLWriter
	 * @see http://stackoverflow.com/a/143350
	 */
	private function initXML($rootElement) {
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		$xml->startDocument('1.0','UTF-8');

		$xml->writeComment('Generated at ' . date('r'));

		// open root element and set "xmlns" attribute
		$xml->startElement($rootElement);
		$xml->writeAttribute('xmlns', self::SITEMAP_NS);

		return $xml;
	}

	/**
	 * Helper method storing XML file
	 */
	private function saveXML(XMLWriter $xml, $fileName, $gzip = true) {
		$fileName = basename($fileName) . ($gzip ? '.gz' : '');

		// close root element and render XML
		$xml->endElement();
		$content = $xml->outputMemory(true);

		// GZIP content (when requested)
		if ($gzip) {
			$content = gzencode($content, 9);
		}

		$size = round(strlen($content) / 1024, 2);
		$this->debug->log(__METHOD__ . ": {$fileName} ({$size} kB)");

		return file_put_contents($this->dir . '/' . $fileName, $content);
	}

	/**
	 * Store sitemap's links in a given file
	 */
	private function saveSitemap($fileName, $gzip = true) {
		// generate XML
		$xml = $this->initXML('urlset');

		foreach($this->urls as $item) {
			$xml->startElement('url');

			// emit URL
			$xml->writeElement('loc', $item['url']);

			// last modification date
			if (isset($item['lastmod'])) {
				$xml->writeElement('lastmod', $item['lastmod']);
			}

			// page priority - <0, 1>
			if (isset($item['priority'])) {
				$xml->writeElement('priority', $item['priority']);
			}

			// close an item
			$xml->endElement();
		}

		// add an entry to sitemaps index
		$this->sitemaps[] = $fileName . ($gzip ? '.gz' : '');

		return $this->saveXML($xml, $fileName, $gzip);
	}

	/**
	 * Store sitemap index file
	 */
	private function saveIndex($fileName, $gzip = true) {
		// generate XML
		$xml = $this->initXML('sitemapindex');

		foreach($this->sitemaps as $item) {
			$xml->startElement('sitemap');

			// emit URL
			$xml->writeElement('loc', $this->router->formatFullUrl() . $item);

			// close an item
			$xml->endElement();
		}

		return $this->saveXML($xml, $fileName, $gzip);
	}

	/**
	 * Add new entry in sitemap index
	 *
	 * Saves sitemap with the current set of URLs and adds an entry to sitemaps index
	 */
	private function addSitemap() {
		// nothing to store
		if (count($this->urls) == 0) {
			return;
		}

		$fileName = sprintf('sitemap-%02d.xml', count($this->sitemaps) + 1);

		// store file
		$this->saveSitemap($fileName, true);
	}

	/**
	 * Add given URL to the list
	 */
	public function addUrl($url, $lastmod = false, $priority = false /* 0.5 is the default value */) {
		$entry = array(
			'url' => $url
		);

		$this->debug->log(__METHOD__ . ": {$url}");

		if ($lastmod !== false) {
			$entry['lastmod'] = is_numeric($lastmod) ? date('Y-m-d', $lastmod /* UNIX timestamp */) : $lastmod;
		}

		if (is_numeric($priority)) {
			$entry['priority'] = max(0, min(1, (float) $priority));
		}

		$this->urls[] = $entry;
		$this->urlsCount++;

		// save next sitemap file if number of URLs is greater then per-file limit
		if (count($this->urls) === self::URLS_PER_FILE) {
			$this->addSitemap();
		}
	}

	/**
	 * Return number of URLs added to the list
	 */
	public function countUrls() {
		return $this->urlsCount;
	}

	/**
	 * Save all remaining links in the sitemap and generate sitemap index
	 */
	public function save() {
		$this->debug->log(__METHOD__ . ": number of URLs - " . $this->countUrls());

		// store any remaining URLs
		$this->addSitemap();

		// store sitemap files index
		$this->saveIndex('sitemap.xml', false /* $gzip */);
		return $this->countUrls();
	}

	/**
	 * Notify given search engine about updated sitemap
	 */
	public function ping($host) {
		// TODO
	}
}
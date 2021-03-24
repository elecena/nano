<?php

/**
 * Helper class for generating XML sitemaps using XMLWriter
 *
 * @see http://www.sitemaps.org/pl/protocol.html
 */

class SitemapGenerator
{
    const SITEMAP_FILE = 'sitemap.xml';
    const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    // Sitemap file that you provide must have no more than 50,000 URLs and must be no larger than 10MB (10,485,760 bytes).
    // @see http://www.sitemaps.org/protocol.html#index
    const URLS_PER_FILE = 15000;

    const USE_GZIP = true; // see issue #11

    private $app;
    private $debug;
    private $dir;
    private $router;

    // list of sitemap's URLs
    private $urls;

    // count URLs in all sitemaps
    private $urlsCount = 0;

    // name of the currently built sitemap
    private $currentSitemap = '';

    // list of sitemaps to be stored in sitemapindex file
    private $sitemaps = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->app = NanoApp::app();

        $this->debug = $this->app->getDebug();
        $this->dir = $this->app->getDirectory() . '/public';
        $this->router = $this->app->getRouter();
    }

    /**
     * Helper metod constructing XMLWriter document
     *
     * @see http://php.net/XMLWriter
     * @see http://stackoverflow.com/a/143350
     *
     * @param string $rootElement
     * @return XMLWriter
     */
    private function initXML($rootElement)
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->writeComment('Generated at ' . date('r'));

        // open root element and set "xmlns" attribute
        $xml->startElement($rootElement);
        $xml->writeAttribute('xmlns', self::SITEMAP_NS);

        return $xml;
    }

    /**
     * Helper method storing XML file
     *
     * @param XMLWriter $xml
     * @param string $fileName
     * @param bool $gzip
     * @return int
     */
    private function saveXML(XMLWriter $xml, $fileName, $gzip = true)
    {
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
     *
     * @param string $fileName
     * @param bool $gzip
     * @return bool|int
     */
    private function saveSitemap($fileName, $gzip = true)
    {
        // nothing to store
        if (empty($this->urls)) {
            return false;
        }

        // generate XML
        $xml = $this->initXML('urlset');
        $xml->writeComment(count($this->urls) . ' items');

        foreach ($this->urls as $item) {
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

        $this->debug->log(__METHOD__ . ": " . count($this->urls) . " items to be stored as {$fileName}");

        // add an entry to sitemaps index
        $this->sitemaps[] = $fileName . ($gzip ? '.gz' : '');

        // reset the list of items
        $this->urls = [];

        return $this->saveXML($xml, $fileName, $gzip);
    }

    /**
     * Store sitemap index file
     *
     * @param string $fileName
     * @param bool $gzip
     * @return int
     */
    private function saveIndex($fileName, $gzip = true)
    {
        // generate XML
        $xml = $this->initXML('sitemapindex');
        $xml->writeComment($this->urlsCount . ' items');

        foreach ($this->sitemaps as $item) {
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
    private function addSitemap()
    {
        // nothing to store
        if (count($this->urls) === 0) {
            return;
        }

        $fileName = sprintf('sitemap-%03d%s.xml', count($this->sitemaps) + 1, ($this->currentSitemap !== '' ? ('-' . $this->currentSitemap) : ''));

        // store file
        $this->saveSitemap($fileName, self::USE_GZIP);
    }

    /**
     * Start new sitemap under given name
     *
     * @param string $name
     */
    public function startSitemap($name)
    {
        // store previously added urls
        $this->addSitemap();

        $this->currentSitemap = $name;

        $this->debug->log(__METHOD__ . ": {$this->currentSitemap}");
    }

    /**
     * Closes sitemap created using startSitemap
     */
    public function endSitemap()
    {
        // store previously added urls
        $this->addSitemap();
        $this->currentSitemap = '';
    }

    /**
     * Add given URL to the list
     *
     * @param string $url
     * @param bool|string|int $lastmod
     * @param bool|int $priority
     */
    public function addUrl($url, $lastmod = false, $priority = false /* 0.5 is the default value */)
    {
        $entry = [
            'url' => $url
        ];

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
    public function countUrls()
    {
        return $this->urlsCount;
    }

    /**
     * Save all remaining links in the sitemap and generate sitemap index
     */
    public function save()
    {
        $this->debug->log(__METHOD__ . ": number of URLs - " . $this->countUrls());

        // store any remaining URLs
        $this->addSitemap();

        // store sitemap files index
        $this->saveIndex(self::SITEMAP_FILE, false /* $gzip */);
        return $this->countUrls();
    }

    /**
     * Notify given search engine about updated sitemap
     *
     * @param string $host
     * @return bool
     */
    public function ping($host)
    {
        $http = new HttpClient();

        $res = $http->get($host . '/ping', [
            'sitemap' => $this->router->formatFullUrl() . self::SITEMAP_FILE
        ]);

        return $res->getResponseCode() === 200;
    }
}

<?php

/**
 * Helper class for generating RSS feeds using XMLWriter
 */
class FeedGenerator
{
    private $app;
    private $debug;

    // list of feed items
    private $items = [];

    private $title;
    private $description;

    public function __construct(NanoApp $app)
    {
        $this->app = $app;
        $this->debug = $app->getDebug();
    }

    /**
     * Helper metod constructing XMLWriter document
     *
     * @see http://php.net/XMLWriter
     * @see http://stackoverflow.com/a/143350
     */
    private function initXML()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->writeComment('Generated at ' . date('r'));

        // open root element and set "xmlns" attribute
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');

        return $xml;
    }

    /**
     * Set feed title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Set feed description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Add an item to feed
     */
    public function addItem($title, $link, $content = false, $time = false)
    {
        $entry = [
            'title' => $title,
            'link' => $link,
        ];

        $this->debug->log(__METHOD__ . ": {$title}");

        if ($time !== false) {
            $entry['pubDate'] = is_numeric($time) ? date('r', $time /* UNIX timestamp */) : $time;
        }

        if ($content !== false) {
            $entry['content'] = $content;
        }

        $this->items[] = $entry;
    }

    /**
     * Render feed as XML string
     */
    public function render()
    {
        // generate XML
        $xml = $this->initXML();

        // generate header
        $xml->startElement('channel');

        $xml->writeElement('title', $this->title);
        $xml->writeElement('description', $this->description);

        foreach ($this->items as $item) {
            $xml->startElement('item');

            // emit URL
            $xml->writeElement('title', $item['title']);
            $xml->writeElement('link', $item['link']);

            if (isset($item['content'])) {
                $xml->writeElement('content', $item['content']);
            }

            if (isset($item['pubDate'])) {
                $xml->writeElement('pubDate', $item['pubDate']);
            }

            $xml->endElement();
        }

        // close <channel> and <rss> elements
        $xml->endElement();
        $xml->endElement();

        // render XML
        $rss = $xml->outputMemory(true);
        return $rss;
    }
}

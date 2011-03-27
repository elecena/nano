<?php

/**
 * Set of unit tests for DOM class
 *
 * $Id$
 */

class DOMTest extends PHPUnit_Framework_TestCase {

	private $html;
	private $xml;

	public function setUp() {
		$this->xml = <<<XML
<root>
	<foo bar="test">
		<bar attr="value" data-foo="1">123</bar>
	</foo>
</root>
XML;

		$this->html = <<<HTML
<p>foo</p>
<ol start="4">
	<li>123
	<li>456
</ol>
HTML;
	}

	public function testParseXml() {
		// parse well formatted XML
		$dom = DOM::newFromXml($this->xml, true /* $stictMode */);

		$this->assertInstanceOf('DOM', $dom);
		$this->assertContains('<root>', (string) $dom);
		$this->assertContains('data-foo', (string) $dom);
		$this->assertEquals('utf-8', $dom->getCharset());

		// XPath
		$this->assertEquals(0, count($dom->xpath('//root/bar')));
		$this->assertNull($dom->getNodeContent('//root/bar'));
		$this->assertNull($dom->getNodeAttr('//foo/bar', 'foo'));
		$this->assertNotNull($dom->xpath('//foo/bar'));
		$this->assertInstanceOf('SimpleXMLElement', $dom->getNode('//foo/bar'));
		$this->assertEquals('123', $dom->getNodeContent('//foo/bar'));
		$this->assertEquals('1', $dom->getNodeAttr('//foo/bar', 'data-foo'));
	}

	public function testParseXmlWithFallback() {
		// parse well formatted XML (with possible fallback to HTML)
		$dom = DOM::newFromXml($this->xml);

		$this->assertInstanceOf('DOM', $dom);
		$this->assertContains('<root>', (string) $dom);
		$this->assertEquals('123', $dom->getNodeContent('//foo/bar'));
		$this->assertEquals('1', $dom->getNodeAttr('//foo/bar', 'data-foo'));
	}

	public function testParseBrokenXml() {
		// parse broken XML (using strict mode)
		$dom = DOM::newFromXml($this->xml . '<foo>', true /* $stictMode */);

		$this->assertNull($dom);
	}

	public function testParseHtmlAsXml() {
		// parse HTML (using XML strict mode)
		$dom = DOM::newFromXml($this->html, true /* $stictMode */);

		$this->assertNull($dom);
	}

	public function testParseHtml() {
		// parse as HTML
		$dom = DOM::newFromHtml($this->html);

		$this->assertInstanceOf('DOM', $dom);
		$this->assertContains("<p>foo</p>\n<ol start=\"4\">", (string) $dom);
		$this->assertContains('</li><li>', (string) $dom);
		$this->assertNull($dom->getCharset());

		// XPath
		$this->assertEquals(2, count($dom->xpath('//ol/li')));
		$this->assertContains('123', $dom->getNodeContent('//ol/li'));
		$this->assertEquals('4', $dom->getNodeAttr('//ol', 'start'));

		$nodes = $dom->xpath('//ol/li');
		$this->assertContains('123', (string) $nodes[0]);
		$this->assertContains('456', (string) $nodes[1]);
	}

	// provider for testHtmlCharset()
	private function getHtml($text, $charset) {
		$html = <<<HTML
<head><meta http-equiv="Content-Type" content="text/html; charset=$charset"/></head>
<p>$text</p>
HTML;

		return $html;
	}

	public function testHtmlCharset() {
		$dom = DOM::newFromHtml($this->getHtml('ąę', 'utf-8'));
		$this->assertContains('ąę', $dom->getNodeContent('//p'));
		$this->assertEquals('utf-8', $dom->getCharset());

		$dom = DOM::newFromHtml($this->getHtml("\xb1\xea", 'iso-8859-2'));
		$this->assertContains('ąę', $dom->getNodeContent('//p'));
		$this->assertEquals('iso-8859-2', $dom->getCharset());

		$dom = DOM::newFromHtml($this->getHtml("\xb1\xea", 'iso-8859-2'), 'iso-8859-2' /* forced charset */);
		$this->assertContains('ąę', $dom->getNodeContent('//p'));
		$this->assertEquals('iso-8859-2', $dom->getCharset());

		$dom = DOM::newFromHtml($this->getHtml("\xb1\xea", 'utf-8'), 'iso-8859-2' /* forced charset */);
		$this->assertContains('ąę', $dom->getNodeContent('//p'));
		$this->assertEquals('iso-8859-2', $dom->getCharset());
	}
}
<?php

/**
 * Set of unit tests for DOM class
 *
 * $Id$
 */

class DOMTest extends PHPUnit_Framework_TestCase {

	public function testParseXml() {
		$xml = <<<XML
<root>
	<foo bar="test">
		<bar attr="value" data-foo="1">123</bar>
	</foo>
</root>
XML;

		// parse well formatted XML
		$dom = DOM::newFromXml($xml, true /* $stictMode */);

		$this->assertInstanceOf('DOM', $dom);
		$this->assertContains('<root>', (string) $dom);
		$this->assertContains('data-foo', (string) $dom);

		// XPath
		$this->assertEquals(0, count($dom->xpath('//root/bar')));
		$this->assertNull($dom->getNodeContent('//root/bar'));
		$this->assertNull($dom->getNodeAttr('//foo/bar', 'foo'));
		$this->assertNotNull($dom->xpath('//foo/bar'));
		$this->assertInstanceOf('SimpleXMLElement', $dom->getNode('//foo/bar'));
		$this->assertEquals('123', $dom->getNodeContent('//foo/bar'));
		$this->assertEquals('1', $dom->getNodeAttr('//foo/bar', 'data-foo'));

		// parse well formatted XML (with possible fallback to HTML)
		$dom = DOM::newFromXml($xml);

		$this->assertInstanceOf('DOM', $dom);
		$this->assertContains('<root>', (string) $dom);
		$this->assertEquals('123', $dom->getNodeContent('//foo/bar'));
		$this->assertEquals('1', $dom->getNodeAttr('//foo/bar', 'data-foo'));

		// parse broken XML (using strict mode)
		$dom = DOM::newFromXml($xml . '<foo>', true /* $stictMode */);

		$this->assertNull($dom);
	}

	public function testParseHtml() {
		$html = <<<HTML
<p>foo</p>
<ol start="4">
	<li>123
	<li>456
</ol>
HTML;

		// parse HTML (using XML strict mode)
		$dom = DOM::newFromXml($html, true /* $stictMode */);

		$this->assertNull($dom);

		// parse as HTML
		$dom = DOM::newFromHtml($html);

		$this->assertInstanceOf('DOM', $dom);
		$this->assertContains('<p>', (string) $dom);
		$this->assertContains('</li><li>', (string) $dom);

		// XPath
		$this->assertEquals(2, count($dom->xpath('//ol/li')));
		$this->assertContains('123', $dom->getNodeContent('//ol/li'));
		$this->assertEquals('4', $dom->getNodeAttr('//ol', 'start'));

		$nodes = $dom->xpath('//ol/li');
		$this->assertContains('123', (string) $nodes[0]);
		$this->assertContains('456', (string) $nodes[1]);
	}
}
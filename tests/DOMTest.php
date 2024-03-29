<?php

/**
 * Set of unit tests for DOM class
 */

class DOMTest extends \Nano\NanoBaseTest
{
    private $html;
    private $xml;

    public function setUp(): void
    {
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
	<li><a href="/foo?bar=test&id=123">789</a>
</ol>
HTML;
    }

    public function testParseXml()
    {
        // parse well formatted XML
        $dom = DOM::newFromXml($this->xml, true /* $stictMode */);

        $this->assertInstanceOf('DOM', $dom);
        $this->assertStringContainsString('<root>', (string) $dom);
        $this->assertStringContainsString('data-foo', (string) $dom);
        $this->assertEquals('utf-8', $dom->getCharset());

        // XPath
        $this->assertEquals(0, count($dom->xpath('//root/bar')));
        $this->assertNull($dom->getNodeContent('//root/bar'));
        $this->assertNull($dom->getNodeAttr('//foo/bar', 'foo'));
        $this->assertNotNull($dom->xpath('//foo/bar'));
        $this->assertInstanceOf('SimpleXMLElement', $dom->getNode('//foo/bar'));
        $this->assertEquals('123', $dom->getNodeContent('//foo/bar'));
        $this->assertEquals('1', $dom->getNodeAttr('//foo/bar', 'data-foo'));
        $this->assertEquals("\n\t\t123\n\t", $dom->getNodeTextContent('//foo'));

        // remove node
        $dom->removeNode('//root//bar');
        $this->assertNull($dom->getNode('//root//bar'));
    }

    public function testParseXmlWithFallback()
    {
        // parse well formatted XML (with possible fallback to HTML)
        $dom = DOM::newFromXml($this->xml);

        $this->assertInstanceOf('DOM', $dom);
        $this->assertStringContainsString('<root>', (string) $dom);
        $this->assertEquals('123', $dom->getNodeContent('//foo/bar'));
        $this->assertEquals('1', $dom->getNodeAttr('//foo/bar', 'data-foo'));
    }

    public function testParseBrokenXml()
    {
        // parse broken XML (using strict mode)
        $dom = DOM::newFromXml($this->xml . '<foo>', true /* $stictMode */);

        $this->assertNull($dom);

        // parse broken XML (with possible fallback to HTML)
        $dom = DOM::newFromXml($this->xml . '<foo>');

        $this->assertInstanceOf('DOM', $dom);
    }

    public function testParseHtmlAsXml()
    {
        // parse HTML (using XML strict mode)
        $dom = DOM::newFromXml($this->html, true /* $stictMode */);

        $this->assertNull($dom);
    }

    public function testParseHtml()
    {
        // parse as HTML
        $dom = DOM::newFromHtml($this->html);

        $this->assertInstanceOf('DOM', $dom);
        $this->assertStringContainsString("<p>foo</p>\n<ol start=\"4\">", (string) $dom);
        $this->assertStringContainsString('</li><li>', (string) $dom);
        $this->assertNull($dom->getCharset());

        // XPath
        $this->assertEquals(3, count($dom->xpath('//ol/li')));
        $this->assertStringContainsString('123', $dom->getNodeContent('//ol/li'));
        $this->assertEquals('4', $dom->getNodeAttr('//ol', 'start'));

        $this->assertEquals("123\n\t456\n\t789", trim($dom->getNodeTextContent('//ol')));
        $this->assertEquals("789\n", $dom->getNodeTextContent('//ol/li[3]'));

        $nodes = $dom->xpath('//ol/li');
        $this->assertStringContainsString('123', (string) $nodes[0]);
        $this->assertStringContainsString('456', (string) $nodes[1]);

        // remove node
        $this->assertEquals(3, count($dom->xpath('//ol/li')));
        $dom->removeNode('//ol/li');
        $this->assertEquals(2, count($dom->xpath('//ol/li')));
    }

    // provider for testHtmlCharset()
    private function getHtml($text, $contentType)
    {
        $html = <<<HTML
<head><meta http-equiv="Content-Type" content="$contentType"/></head>
<p>$text</p>
HTML;

        return $html;
    }

    public function testHtmlCharset()
    {
        $utfContent = 'ąę';
        $isoContent = "\xb1\xea";

        $dom = DOM::newFromHtml($this->getHtml($utfContent, 'text/html; charset=utf-8'));
        $this->assertStringContainsString('ąę', $dom->getNodeContent('//p'));
        $this->assertEquals('utf-8', $dom->getCharset());

        $dom = DOM::newFromHtml($this->getHtml($isoContent, 'text/html; charset=iso-8859-2'));
        $this->assertStringContainsString('ąę', $dom->getNodeContent('//p'));
        $this->assertEquals('iso-8859-2', $dom->getCharset());

        $dom = DOM::newFromHtml($this->getHtml($isoContent, 'text/html; charset=iso-8859-2'), 'iso-8859-2' /* forced charset */);
        $this->assertStringContainsString('ąę', $dom->getNodeContent('//p'));
        $this->assertEquals('iso-8859-2', $dom->getCharset());

        $dom = DOM::newFromHtml($this->getHtml($isoContent, 'text/html; charset=utf-8'), 'iso-8859-2' /* forced charset */);
        $this->assertStringContainsString('ąę', $dom->getNodeContent('//p'));
        $this->assertEquals('iso-8859-2', $dom->getCharset());

        // some sites emit incorrect content type meta entry
        // <meta http-equiv="content-type" content="text/html; iso-8859-2" />
        $dom = DOM::newFromHtml($this->getHtml($isoContent, 'text/html; iso-8859-2'));
        $this->assertStringContainsString('ąę', $dom->getNodeContent('//p'));
        $this->assertEquals('iso-8859-2', $dom->getCharset());

        $dom = DOM::newFromHtml($this->getHtml($isoContent, 'text/html;iso-8859-2'));
        $this->assertStringContainsString('ąę', $dom->getNodeContent('//p'));
        $this->assertEquals('iso-8859-2', $dom->getCharset());
    }
}

<?php

use Nano\Http\Response;

/**
 * Set of unit tests for Nano\Http\Response class
 */
class HttpResponseTest extends \Nano\NanoBaseTest
{
    public function testHttpResponse()
    {
        $resp = new Response();

        $resp->setContent('foo');
        $this->assertEquals('foo', $resp->getContent());
        $this->assertEquals('foo', (string) $resp);

        $resp->setHeaders(['foo' => 'bar']);
        $this->assertEquals('bar', $resp->getHeader('foo'));

        $resp->setResponseCode(200);
        $this->assertEquals(200, $resp->getResponseCode());

        $resp->setLocation('http://example.com');
        $this->assertEquals('http://example.com', $resp->getLocation());
    }
}

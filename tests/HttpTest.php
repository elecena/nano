<?php

use Nano\Http\ResponseException;
use Nano\NanoBaseTest;

/**
 * Set of unit tests for Http class
 *
 * @see https://httpbin.org
 *
 * @covers Http
 * @covers HttpClient
 */
class HttpTest extends NanoBaseTest
{
    /**
     * @throws ResponseException
     */
    public function testGet()
    {
        $url = 'https://httpbin.org/get';

        $resp = Http::get($url);

        $this->assertEquals(200, $resp->getResponseCode());
        $this->assertEquals($url, $resp->getLocation());
        $this->assertEquals('application/json', $resp->getHeader('content-type'));
        $this->assertNull($resp->getHeader('Content-Type'), 'Headers are case sensitive!');

        $json = json_decode($resp->getContent(), true);
        $this->assertEquals($url, $json['url']);

        $userAgent = $json['headers']['User-Agent'];

        $this->assertStringContainsString('NanoPortal/' . Nano::VERSION, $userAgent, 'Nano version is exposed');
        $this->assertStringContainsString('libcurl/', $userAgent, 'libcurl version is exposed');
        $this->assertStringContainsString('php/' . phpversion(), $userAgent, 'PHP version is exposed');
    }

    /**
     * @throws ResponseException
     */
    public function testPost()
    {
        $resp = Http::post('https://httpbin.org/post', ['foo' => 'bar']);

        $this->assertEquals(200, $resp->getResponseCode());
        $this->assertEquals('https://httpbin.org/post', $resp->getLocation());
        $this->assertEquals('application/json', $resp->getHeader('content-type'));

        $json = json_decode($resp->getContent(), true);
        $this->assertEquals(['foo' => 'bar'], $json['form']);
        $this->assertEquals('application/x-www-form-urlencoded', $json['headers']['Content-Type']);
    }

    /**
     * @throws ResponseException
     */
    public function testHead()
    {
        $resp = Http::head('https://httpbin.org');
        $this->assertEquals(200, $resp->getResponseCode());
        $this->assertEquals('', $resp->getContent(), 'No content is returned');
    }
}

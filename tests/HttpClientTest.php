<?php

/**
 * Set of unit tests for HttpClient class
 */

class HttpClientTest extends \Nano\NanoBaseTest
{
    public function testUserAgent()
    {
        $client = new HttpClient();

        // check user agent
        $this->assertMatchesRegularExpression('#NanoPortal/' . Nano::VERSION . '#', $client->getUserAgent());
        $this->assertMatchesRegularExpression('#libcurl/#', $client->getUserAgent());
    }

    public function testInvalidRequest()
    {
        $this->expectException(\Nano\Http\ResponseException::class);
        $client = new HttpClient();
        $client->get('foo://bar');
    }

    public function testCookiesJar()
    {
        $client = new HttpClient();

        // create cookie jar file
        $jarFile = tempnam(dirname(__FILE__) . '/app/cache', 'jar');

        $client->setTimeout(0);
        $client->useCookieJar($jarFile);

        $client->get('http://www.google.com/search', ['q' => 'nano']);

        // close HTTP session
        $client->close();

        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

        // check cookies
        $this->assertFileExists($jarFile);
        $this->assertStringContainsString('Cookie File', file_get_contents($jarFile));

        // remove jar file
        unlink($jarFile);
        $this->assertFileDoesNotExist($jarFile);
    }
}

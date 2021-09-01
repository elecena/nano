<?php

use Nano\Http\ResponseException;
use Nano\NanoBaseTest;

/**
 * Set of unit tests for HttpClient class
 */

class HttpClientTest extends NanoBaseTest
{
    private $jarFile;

    public function setUp(): void
    {
        $this->jarFile = tempnam(sys_get_temp_dir(), 'jar_of_cookies_');
    }

    public function tearDown(): void
    {
        if (file_exists($this->jarFile)) {
            unlink($this->jarFile);
        }
    }

    /**
     * @throws ResponseException
     */
    public function testCookiesJar()
    {
        $client = new HttpClient();

        // create cookie jar file
        $client->useCookieJar($this->jarFile);

        // set a cookie manually
        $client->setCookie('another-one', 'tasty');

        // set a cookie via HTTP response header
        $resp = $client->get('https://httpbin.org/cookies/set', ['cookie' => 'yummy']);
        $this->assertEquals(200, $resp->getResponseCode());
        $this->assertStringContainsString('cookie=yummy', $resp->getHeader('set-cookie'), 'Cookie is set');

        // check cookies
        $this->assertFileExists($this->jarFile, 'Jar file is created');

        // are cookies kept within the session?
        $resp = $client->get('https://httpbin.org/cookies');

        $body = json_decode($resp->getContent(), true);
        $this->assertCount(2, $body['cookies'], 'Two cookies are set');
        $this->assertEquals('tasty', $body['cookies']['another-one']);
        $this->assertEquals('yummy', $body['cookies']['cookie']);

        // close HTTP session
        $client->close();
    }
}

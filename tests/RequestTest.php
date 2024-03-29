<?php

/**
 * Set of unit tests for Request class
 */

use Nano\Request;

class RequestTest extends \Nano\NanoBaseTest
{
    public function testGETParams()
    {
        // emulate GET request
        $request = new Request(
            [
            'foo' => 'bar',
            'test' => '2',
            'box' => 'on',
        ],
            [
            'REQUEST_METHOD' => 'GET',
        ]
        );

        $this->assertFalse($request->wasPosted());

        $this->assertNull($request->get('foo2'));
        $this->assertEquals('bar', $request->get('foo'));
        $this->assertEquals(0, $request->getInt('foo'));
        $this->assertEquals(2, $request->getInt('test'));
        $this->assertTrue($request->getChecked('box'));
        $this->assertFalse($request->getChecked('box2'));

        // change request's type
        $request->setType(Request::POST);
        $this->assertTrue($request->wasPosted());

        $request->setType(Request::API);
        $this->assertTrue($request->isApi());
        $this->assertFalse($request->wasPosted());
    }

    public function testGETParamsFromArray()
    {
        // emulate GET request
        $request = Request::newFromArray([
            'foo' => 'bar',
            'test' => '2',
            'box' => 'on',
        ]);

        $this->assertFalse($request->wasPosted());
        $this->assertFalse($request->isInternal());
        $this->assertFalse($request->isCLI());

        $this->assertNull($request->get('foo2'));
        $this->assertEquals('bar', $request->get('foo'));
        $this->assertTrue($request->getChecked('box'));
    }

    public function testPOSTParams()
    {
        // emulate POST request
        $request = new Request(
            [
            'foo' => 'bar',
            'test' => '2',
            'box' => 'on',
        ],
            [
            'REQUEST_METHOD' => 'POST',
        ]
        );

        $this->assertTrue($request->wasPosted());
        $this->assertFalse($request->isInternal());

        $this->assertNull($request->get('test2'));
        $this->assertEquals('bar', $request->get('foo'));
        $this->assertEquals(0, $request->getInt('foo'));
        $this->assertEquals(2, $request->getInt('test'));
        $this->assertTrue($request->getChecked('box'));
        $this->assertFalse($request->getChecked('box2'));
    }

    public function testPOSTParamsFromArray()
    {
        // emulate POST request
        $request = Request::newFromArray([
            'foo' => 'bar',
            'test' => '2',
            'box' => 'on',
        ], Request::POST);

        $this->assertTrue($request->wasPosted());

        $this->assertNull($request->get('foo2'));
        $this->assertEquals('bar', $request->get('foo'));
        $this->assertTrue($request->getChecked('box'));
    }

    public function testGetIntLimit()
    {
        // emulate GET request
        $request = Request::newFromArray([
            'limit' => '20',
        ]);

        $this->assertEquals(20, $request->getInt('limit'));
        $this->assertEquals(20, $request->getIntLimit('limit', 0, 25));
        $this->assertEquals(15, $request->getIntLimit('limit', 0, 15));
        $this->assertEquals(25, $request->getIntLimit('limit', 25, 50));

        $this->assertEquals(5, $request->getIntLimit('test', 5, 15));
        $this->assertEquals(0, $request->getIntLimit('test', 5, 15, 'foo'));
        $this->assertEquals(10, $request->getIntLimit('test', 20, 25, 10));
        $this->assertEquals(50, $request->getIntLimit('test', 0, 25, 50));
    }

    public function testGetExtension()
    {
        $request = Request::newFromPath('/foo/bar');
        $this->assertNull($request->getExtension());

        $request = Request::newFromPath('/static/js/foo.js');
        $this->assertEquals('js', $request->getExtension());

        $request = Request::newFromPath('/static/foo.css');
        $this->assertEquals('css', $request->getExtension());

        $request = Request::newFromPath('/files/foo.bar.gz');
        $this->assertEquals('gz', $request->getExtension());
    }

    public function testApi()
    {
        $request = new Request(
            [
            'foo' => 'bar',
            'test' => '2',
            'box' => 'on',
        ],
            [
            'REQUEST_METHOD' => 'API',
        ]
        );

        $this->assertTrue($request->isApi());
        $this->assertFalse($request->wasPosted());

        $request = Request::newFromPath('/foo/bar', [], Request::INTERNAL);
        $this->assertTrue($request->isInternal());
        $this->assertEquals('/foo/bar', $request->getPath());
    }

    public function testInternal()
    {
        $request = new Request(
            [
            'foo' => 'bar',
            'test' => '2',
            'box' => 'on',
        ],
            [
            'REQUEST_METHOD' => 'INTERNAL',
        ]
        );

        $this->assertTrue($request->isInternal());

        $request = Request::newFromPath('/foo/bar', [], Request::INTERNAL);
        $this->assertTrue($request->isInternal());
        $this->assertEquals('/foo/bar', $request->getPath());
    }

    public function testCLI()
    {
        $request = new Request(
            [
            'foo' => 'bar',
            'test' => '2',
            'box' => 'on',
        ],
            [
            'REQUEST_METHOD' => 'CLI',
        ]
        );

        $this->assertTrue($request->isCLI());

        $request = Request::newFromPath('/foo/bar', [], Request::CLI);
        $this->assertTrue($request->isCLI());
        $this->assertEquals('/foo/bar', $request->getPath());
    }

    public function testRequestPath()
    {
        $uri = '/test/unit/bar';
        $uriWithParams = $uri . '?q=123&abc=456';

        // set path directly
        $request = new Request([
            'q' => 'foo',
        ]);
        $request->setPath($uri);

        $this->assertEquals($uri, $request->getPath());
        $this->assertEquals('foo', $request->get('q'));

        // create from REQUEST_URI
        $request = Request::newFromRequestURI($uriWithParams);

        $this->assertFalse($request->wasPosted());
        $this->assertEquals($uri, $request->getPath());
        $this->assertEquals('123', $request->get('q'));
        $this->assertEquals('456', $request->get('abc'));

        // create from path
        $request = Request::newFromPath($uri);

        $this->assertFalse($request->wasPosted());
        $this->assertEquals($uri, $request->getPath());

        // create from path and params
        $request = Request::newFromPath($uri, ['q' => 'foo'], Request::POST);

        $this->assertTrue($request->wasPosted());
        $this->assertEquals($uri, $request->getPath());
        $this->assertEquals('foo', $request->get('q'));
    }

    public function testRequestPathNormalize()
    {
        $request = new Request([
            'q' => '123',
        ], [
            'REQUEST_URI' => '/nanoportal/foo/bar.json?q=123',
            'SCRIPT_NAME' => '/nanoportal/api.php',
        ]);

        $this->assertEquals('/foo/bar.json', $request->getPath());
        $this->assertEquals('123', $request->get('q'));

        $request = new Request([
            'q' => '123',
        ], [
            'REQUEST_URI' => '/foo/bar.json?q=123',
            'SCRIPT_NAME' => '/api.php',
        ]);

        $this->assertEquals('/foo/bar.json', $request->getPath());
        $this->assertEquals('123', $request->get('q'));
    }

    public function testRequestPathDecoding()
    {
        $request = new Request([], ['REQUEST_URI' => '/foo/bar%20test']);

        $this->assertEquals('/foo/bar test', $request->getPath());
    }

    public function testGetHeader()
    {
        $request = new Request([], ['HTTP_FOO' => 'bar']);

        $this->assertEquals('bar', $request->getHeader('Foo'));

        // not existing header
        $this->assertNull($request->getHeader('test'));
        $this->assertEquals('foo', $request->getHeader('test', 'foo'));
    }

    public function testIP()
    {
        // crawl-66-249-66-248.googlebot.com
        $ip = '66.249.66.248';
        $local = '192.168.1.146';

        $request = new Request([], ['HTTP_CLIENT_IP' => $ip]);
        $this->assertEquals($ip, $request->getIP());
        $this->assertEquals($ip, $request->getIP()); // should be served from "local" cache

        $request = new Request([], ['REMOTE_ADDR' => $ip]);
        $this->assertEquals($ip, $request->getIP());

        $request = new Request([], ['HTTP_X_FORWARDED_FOR' => $ip]);
        $this->assertEquals($ip, $request->getIP());

        $request = new Request([], ['HTTP_X_FORWARDED_FOR' => $ip, 'REMOTE_ADDR' => $local]);
        $this->assertEquals($ip, $request->getIP());

        // no IP provided
        $request = new Request([]);
        $this->assertNull($request->getIP());

        // only local IP provided
        $request = new Request([], ['HTTP_X_FORWARDED_FOR' => $local]);
        $this->assertNull($request->getIP());

        // test helper method
        $this->assertFalse(request::isLocalIP($ip));
        $this->assertTrue(request::isLocalIP($local));
    }
}

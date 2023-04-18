<?php

/**
 * Set of unit tests for Router class
 */

use Nano\NanoBaseTest;
use Nano\Router;

class RouterTest extends NanoBaseTest
{
    /**
     * @param array $customConfigSettings custom Config settings to apply to NanoApp
     * @return Router
     */
    private function getRouter(array $customConfigSettings = []): Router
    {
        // use test application's directory
        $this->app = Nano::app(__DIR__ . '/app');

        foreach ($customConfigSettings as $key => $value) {
            $this->app->getConfig()->set($key, $value);
        }

        return new Router($this->app);
    }

    /**
     * @dataProvider getPathPrefixDataProvider
     */
    public function testGetPathPrefix(string $homeUrl, string $expected)
    {
        $router = $this->getRouter(['home' => $homeUrl]);
        $this->assertEquals($expected, $router->getPathPrefix());
    }

    public static function getPathPrefixDataProvider(): Generator
    {
        // parse_url( PHP_URL_PATH ) gives null in this case
        yield 'Home page at root' => [
            'homeUrl' => 'https://foo.bar',
            'expected' => '/',
        ];

        yield 'Home page at /' => [
            'homeUrl' => 'https://foo.bar/',
            'expected' => '/',
        ];

        yield 'Home page at /site' => [
            'homeUrl' => 'https://foo.bar/site',
            'expected' => '/site/',
        ];
    }

    public function testSanitize()
    {
        $router = $this->getRouter();

        $this->assertEquals('', $router->sanitize(null));
        $this->assertEquals('foobar', $router->sanitize('foobar'));
        $this->assertEquals('foo-bar', $router->sanitize('foo/bar'));
        $this->assertEquals('foo-bar', $router->sanitize('foo - bar'));
        $this->assertEquals('foobar-123', $router->sanitize('foobar 123'));
        $this->assertEquals('foo-bar', $router->sanitize('foo bar'));
        $this->assertEquals('foo-bar', $router->sanitize('foo bar $'));
        $this->assertEquals('foo-bar', $router->sanitize('foo $ bar'));
        $this->assertEquals('foo-bar', $router->sanitize('foo  bar'));
        $this->assertEquals('foo-bar', $router->sanitize(' foo bar '));

        // utf
        $this->assertEquals('aaa', $router->sanitize('aąa'));
        $this->assertEquals('zazolc-gesla-jazn', $router->sanitize('zażółć gęślą jaźń'));
    }

    public function testFormatUrl()
    {
        $router = $this->getRouter();

        // home URL: http://example.org/site/
        $this->assertEquals('/site/', $router->formatUrl(''));
        $this->assertEquals('/site/foo/bar', $router->formatUrl('foo/bar'));
        $this->assertEquals('/site/foo/bar', $router->formatUrl('/foo/bar/'));
        $this->assertEquals('/site/foo/bar', $router->formatUrl('foo', 'bar'));
        $this->assertEquals('/site/foo/bar?q=test', $router->formatUrl('foo/bar/', ['q' => 'test']));
        $this->assertEquals('/site/foo/bar?q=test', $router->formatUrl('foo', 'bar', ['q' => 'test']));
        $this->assertEquals('/site/foo/bar/test?q=test&foo=bar+test', $router->formatUrl('foo', 'bar', 'test', ['q' => 'test', 'foo' => 'bar test']));
    }

    public function testFormatFullUrl()
    {
        $router = $this->getRouter();

        $this->assertEquals('http://example.org/site/', $router->formatFullUrl('/'));
        $this->assertEquals('http://example.org/site/foo/bar?q=test&foo=1', $router->formatFullUrl('/foo/bar/', ['q' => 'test', 'foo' => 1]));
        $this->assertEquals('http://example.org/site/foo/bar/test?q=test&foo=bar+test', $router->formatFullUrl('foo', 'bar', 'test', ['q' => 'test', 'foo' => 'bar test']));
    }

    public function testLastRoute()
    {
        $router = $this->getRouter();
        $request = $this->app->getRequest();

        $this->assertNull($router->getLastRoute());

        // $config['index'] = '/foo/index';
        $request->setPath('/');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => Router::DEFAULT_METHOD, 'params' => []], $router->getLastRoute());

        $request->setPath('/bar');
        $router->route($request);
        $this->assertNull($router->getLastRoute());

        $request->setPath('/foo');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => Router::DEFAULT_METHOD, 'params' => []], $router->getLastRoute());

        $request->setPath('/foo/bar');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => 'bar', 'params' => []], $router->getLastRoute());

        $request->setPath('/foo/bar/31451');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => 'bar', 'params' => ['31451']], $router->getLastRoute());

        $request->setPath('/foo/bar/31451/test');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => 'bar', 'params' => ['31451', 'test']], $router->getLastRoute());

        $request->setPath('/foo/bar/314-51/_test');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => 'bar', 'params' => ['314-51', '_test']], $router->getLastRoute());

        $request->setPath('/foo/test');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => Router::DEFAULT_METHOD, 'params' => ['test']], $router->getLastRoute());

        // unroutable requests
        $request->setPath('/bar/_test/123');
        $router->route($request);
        $this->assertNull($router->getLastRoute());

        $request->setPath('/bar/apiBar/123');
        $router->route($request);
        $this->assertNull($router->getLastRoute());
    }

    public function testMaps()
    {
        $router = $this->getRouter();
        $request = $this->app->getRequest();

        // test route mapping
        $router->map('/test', '/foo/bar/123');
        $router->map('show/*', '/foo/bar/*');
        $router->map('show', '/foo/');
        $router->map('show/abc', '/foo/test');

        // home page routing
        $router->map('', '/foo/123');

        $request->setPath('/test');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => 'bar', 'params' => [123]], $router->getLastRoute());

        $request->setPath('/test/123');
        $router->route($request);
        $this->assertNull($router->getLastRoute());

        $request->setPath('/show/456');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => 'bar', 'params' => [456]], $router->getLastRoute());

        $request->setPath('/show');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => Router::DEFAULT_METHOD, 'params' => []], $router->getLastRoute());

        $request->setPath('/show/abc');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => Router::DEFAULT_METHOD, 'params' => ['test']], $router->getLastRoute());

        $request->setPath('/');
        $router->route($request);
        $this->assertEquals(['controller' => 'foo', 'method' => Router::DEFAULT_METHOD, 'params' => ['123']], $router->getLastRoute());

        // empty path mapping
        $router->map('', '');

        $request->setPath('/');
        $router->route($request);
        $this->assertNull($router->getLastRoute());
    }
}

<?php

use Nano\Response;
use Nano\Request;

/**
 * Set of unit tests for StaticAssets class
 */
class StaticAssetsTest extends \Nano\NanoBaseTest
{
    private int $cb = 0;

    public function setUp(): void
    {
        // use test application's directory
        $dir = realpath(__DIR__ . '/app');
        $this->app = Nano::app($dir);

        // set a randnm cb value
        $this->cb = rand();
        $this->app->getConfig()->set('assets.cb', $this->cb);

        // register a package
        $this->app->getConfig()->set('assets.packages', [
            'core' => [
                'js' => [
                    '/statics/head.load.min.js',
                ],
            ],
            'foo' => [
                'js' => '/statics/jquery.foo.js',
                'css' => '/statics/reset.css',
                'deps' => 'core',
            ],
            'jquery' => [
                'js' => '/statics/jquery.foo.js',
                'ext' => [
                    'js' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js',
                ],
            ],
        ]);
    }

    /**
     * @return StaticAssets
     */
    private function getStaticAssets()
    {
        return new StaticAssets($this->app);
    }

    public function testGetCacheBuster()
    {
        $static = $this->getStaticAssets();
        $this->assertEquals($this->cb, $static->getCacheBuster());
    }

    public function testGetProcessor()
    {
        $static = $this->getStaticAssets();
        $this->assertInstanceOf('StaticAssets', $static);

        // processors
        $this->assertInstanceOf('StaticAssetsCss', $static->getProcessor('css'));
        $this->assertInstanceOf('StaticAssetsJs', $static->getProcessor('js'));
    }

    public function testGetProcessorFails()
    {
        $static = $this->getStaticAssets();

        // the following will throw an exception
        $this->expectException('Exception');
        $this->expectExceptionMessage('StaticAssetsProcessor StaticAssetsFoobar not found');

        $static->getProcessor('foobar');
    }

    public function testServeTypeCheck()
    {
        $assets = [
            // unsupported file types
            '/foo/bar' => false,
            '/test.xml' => false,
            '/package/app' => false,
            // correct file types
            '/statics/jquery.foo.js' => true,
            '/statics/reset.css' => true,
            '/statics/blank.gif' => true,
            '/statics/rss.png' => true,
            '/statics/favicon.ico' => true,
            '/statics/favicon.svg' => true,
            // package
            '/package/core.js' => true,
            '/package/foo.css' => true,
        ];

        foreach ($assets as $asset => $expected) {
            $request = Request::newFromPath($asset);
            $static = $this->getStaticAssets();
            $response = $this->app->getResponse();

            $this->assertEquals($expected, $static->serve($request), "The StaticAssetts::serve('{$asset}') should return " . json_encode($expected));
            $this->assertEquals($expected ? Response::OK : Response::NOT_IMPLEMENTED, $response->getResponseCode(), 'Response code should maych');
        }
    }

    public function testPackageExists()
    {
        $static = $this->getStaticAssets();

        $this->assertTrue($static->packageExists('core'));
        $this->assertFalse($static->packageExists('fake'));
    }

    public function testFilterOutEmptyPackages()
    {
        $static = $this->getStaticAssets();

        $this->assertEquals(['core'], $static->filterOutEmptyPackages(['core'], 'js'));
        $this->assertEquals([], $static->filterOutEmptyPackages(['core'], 'css'));

        $this->assertEquals(['core', 'foo'], $static->filterOutEmptyPackages(['core', 'foo'], 'js'));
        $this->assertEquals(['foo'], $static->filterOutEmptyPackages(['core', 'foo'], 'css'));

        $this->assertEquals(['core', 'foo'], $static->filterOutEmptyPackages(['core', 'foo', 'fake'], 'js'));
    }

    public function testResolveDependencies()
    {
        // fake packages config
        $packages = [
            'core' => [
                // no dependencies
            ],
            'libFoo' => [
                'deps' => 'core',
            ],
            'libBar' => [
                'deps' => 'libFoo',
            ],
            'libTest' => [
                'deps' => ['libBar', 'libFoo'],
            ],
        ];
        $this->app->getConfig()->set('assets.packages', $packages);

        $static = $this->getStaticAssets();

        // fake package dependency
        $this->assertFalse($static->resolveDependencies(['fake']));

        // single package dependencies
        $this->assertEquals(
            ['core'],
            $static->resolveDependencies(['core'])
        );
        $this->assertEquals(
            ['core', 'libFoo'],
            $static->resolveDependencies(['libFoo'])
        );
        $this->assertEquals(
            ['core', 'libFoo'],
            $static->resolveDependencies(['libFoo'])
        );
        $this->assertEquals(
            ['core', 'libFoo', 'libBar', 'libTest'],
            $static->resolveDependencies(['libTest'])
        );

        // multiple packages dependencies
        $this->assertEquals(
            ['core', 'libFoo', 'libBar'],
            $static->resolveDependencies(['libFoo', 'libBar'])
        );
        $this->assertEquals(
            ['core', 'libFoo', 'libBar'],  // keep the order of dependencies
            $static->resolveDependencies(['libBar', 'libFoo'])
        );
        $this->assertEquals(
            ['core', 'libFoo', 'libBar', 'libTest'],
            $static->resolveDependencies(['libBar', 'libFoo', 'libTest'])
        );
    }

    public function testNotFoundAsset()
    {
        $assets = [
            // existing file (and with cache buster)
            '/statics/rss.png' => true,
            '/r200/statics/rss.png' => true,
            // not existing file
            '/statics/404.js' => false,
            '/statics/not-existing.png' => false,
            // not existing package
            '/package/test.js' => false,
            '/package/not-existing.css' => false,
        ];

        foreach ($assets as $asset => $expected) {
            $request = Request::newFromPath($asset);
            $static = $this->getStaticAssets();
            $response = $this->app->getResponse();

            $this->assertEquals($expected, $static->serve($request));
            $this->assertEquals($expected ? Response::OK : Response::NOT_FOUND, $response->getResponseCode());
        }
    }

    public function testGetLocalPath()
    {
        $static = $this->getStaticAssets();
        $root = $this->app->getDirectory();

        $assets = [
            '/statics/blank.gif' => $root . '/statics/blank.gif',
            '/statics/r300.gif' => $root . '/statics/r300.gif',
            '/release/blank.gif' => $root . '/release/blank.gif',
            '/r1/statics/blank.gif' => $root . '/statics/blank.gif',
            '/r200/statics/blank.gif' => $root . '/statics/blank.gif',
            '/r200/statics/r300.gif' => $root . '/statics/r300.gif',
        ];

        foreach ($assets as $path => $local) {
            $this->assertEquals($local, $static->getLocalPath($path));
        }
    }
    public function testGetUrlForAssetAndPackage()
    {
        // cache buster prepended (default behaviour)
        $static = $this->getStaticAssets();
        $cb = $static->getCacheBuster();

        $this->assertNull($static->getCDNPath());

        $this->assertEquals("/site/r{$cb}/statics/jquery.foo.js", $static->getUrlForAsset('/statics/jquery.foo.js'));
        $this->assertEquals(["/site/r{$cb}/package/core.js"], $static->getUrlsForPackage('core', 'js'));
        $this->assertEquals(["/site/r{$cb}/package/foo.css"], $static->getUrlsForPackage('foo', 'css'));
        $this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));

        $this->assertEquals(["/site/r{$cb}/package/core,foo.js"], $static->getUrlsForPackages(['core', 'foo'], 'js'));

        // cache buster appended
        $this->app->getConfig()->set('assets.prependCacheBuster', false);

        $static = $this->getStaticAssets();
        $cb = $static->getCacheBuster();

        $this->assertEquals("/site/statics/jquery.foo.js?r={$cb}", $static->getUrlForAsset('/statics/jquery.foo.js'));
        $this->assertEquals(["/site/package/core.js?r={$cb}"], $static->getUrlsForPackage('core', 'js'));
        $this->assertEquals(["/site/package/foo.css?r={$cb}"], $static->getUrlsForPackage('foo', 'css'));
        $this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));
        $this->assertEquals(["/site/package/core,foo.js?r={$cb}"], $static->getUrlsForPackages(['core', 'foo'], 'js'));
    }

    public function testGetUrlForFile()
    {
        // cache buster prepended (default behaviour)
        $static = $this->getStaticAssets();
        $cb = $static->getCacheBuster();

        $root = $this->app->getDirectory();

        $this->assertNull($static->getCDNPath());

        $this->assertEquals("/site/r{$cb}/statics/head.js", $static->getUrlForFile($root . '/statics/head.js'));
    }

    public function testGetUrlForAssetAndPackageWithCDN()
    {
        $cdnPath = 'https://cdn.net/sitepath';
        $this->app->getConfig()->set('assets.cdnPath', $cdnPath);

        $static = $this->getStaticAssets();
        $cb = $static->getCacheBuster();

        $this->assertSame($cdnPath, $static->getCDNPath());

        $this->assertEquals("{$cdnPath}/r{$cb}/statics/jquery.foo.js", $static->getUrlForAsset('/statics/jquery.foo.js'));
        $this->assertEquals(["{$cdnPath}/r{$cb}/package/core.js"], $static->getUrlsForPackage('core', 'js'));
        $this->assertEquals(["{$cdnPath}/r{$cb}/package/foo.css"], $static->getUrlsForPackage('foo', 'css'));
        $this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));
        $this->assertEquals(["{$cdnPath}/r{$cb}/package/core,foo.js"], $static->getUrlsForPackages(['core', 'foo'], 'js'));

        // cache buster appended
        $this->app->getConfig()->set('assets.prependCacheBuster', false);

        $static = $this->getStaticAssets();
        $cb = $static->getCacheBuster();

        $this->assertEquals("{$cdnPath}/statics/jquery.foo.js?r={$cb}", $static->getUrlForAsset('/statics/jquery.foo.js'));
        $this->assertEquals(["{$cdnPath}/package/core.js?r={$cb}"], $static->getUrlsForPackage('core', 'js'));
        $this->assertEquals(["{$cdnPath}/package/foo.css?r={$cb}"], $static->getUrlsForPackage('foo', 'css'));
        $this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));
        $this->assertEquals(["{$cdnPath}/package/core,foo.js?r={$cb}"], $static->getUrlsForPackages(['core', 'foo'], 'js'));
    }

    public function testGetUrlForExternalPackage()
    {
        // cache buster prepended (default behaviour)
        $static = $this->getStaticAssets();
        $cb = $static->getCacheBuster();

        $this->assertNull($static->getCDNPath());

        $this->assertEquals([
            'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js',
            "/site/r{$cb}/package/jquery.js",
        ], $static->getUrlsForPackage('jquery', 'js'));
    }

    public function testCssMinify()
    {
        $static = $this->getStaticAssets();
        $processor = $static->getProcessor('css');

        // original CSS => minifiied
        $css = [
            'body, p {padding: 5px 0px; margin:  10px;}' => 'body,p{padding:5px 0;margin:10px}',
            '.foo .bar {padding: 10px 1px 0em 0.5em}' => '.foo .bar{padding:10px 1px 0 .5em}',
            '.foo > .bar {padding: 0.75em 0px;}' => '.foo > .bar{padding:.75em 0}',
            'mark    {background-color: #eeeeee; color: #333}' => 'mark{background-color:#eee;color:#333}',
            '.foo {padding: 0 0 15px 0px;}' => '.foo{padding:0 0 15px 0}',
            '.foo {margin: 0 0 25px;}' => '.foo{margin:0 0 25px}',
        ];

        foreach ($css as $in => $out) {
            // temporary file to use for processing
            $file = Utils::getTempFile();

            file_put_contents($file, $in);
            $this->assertStringContainsString($out, $processor->processFiles([$file]));

            // clean up
            unlink($file);
        }
    }

    public function testImageEncoding()
    {
        $dir = $this->app->getDirectory() . '/statics';
        $static = $this->getStaticAssets();
        $processor = $static->getProcessor('css');

        // encode existing image
        $this->assertStringContainsString('data:image/png;base64,', $processor->encodeImage($dir . '/rss.png'));

        // error handling
        $this->assertFalse($processor->encodeImage($dir . '/not-existing.png'));
        $this->assertFalse($processor->encodeImage($dir . '/file.xml'));
        $this->assertFalse($processor->encodeImage(__FILE__));

        // blank.gif embedding
        $this->assertEquals('data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw==', $processor->encodeImage($dir . '/blank.gif'));
        $this->assertStringContainsString('.foo{background-image:url(data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw==)}', $processor->processFiles([$dir . '/blank.css']));

        // big files should not be encoded
        $this->assertStringContainsString('php-logo.jpg', $processor->processFiles([$dir . '/php-logo.css']));
    }

    public function testCssInclude()
    {
        $dir = $this->app->getDirectory() . '/statics';
        $static = $this->getStaticAssets();
        $processor = $static->getProcessor('css');

        // include reset.css file
        $out = $processor->processFiles([$dir . '/blank.css']);
        $this->assertFalse(strpos($out, '@import'));
        $this->assertStringContainsString('html,body,h1,h2,h3,h4,h5,h6', $out);
    }

    public function testJsMinify()
    {
        $dir = $this->app->getDirectory() . '/statics';
        $static = $this->getStaticAssets();
        $processor = $static->getProcessor('js');

        // min.js file should not be touched
        $this->assertStringContainsString(file_get_contents($dir . '/head.load.min.js'), $processor->processFiles([$dir . '/head.load.min.js']));

        // minify simple script
        $this->assertStringContainsString('jQuery.fn.foo=function(', $processor->processFiles([$dir . '/jquery.foo.js']));

        // do not modify in debug mode
        $static = $this->getStaticAssets();
        $static->setDebugMode(true);
        $processor = $static->getProcessor('js');

        $this->assertStringContainsString('jQuery.fn.foo = function(bar) {', $processor->processFiles([$dir . '/jquery.foo.js']));
    }

    public function testGetPackageName()
    {
        $static = $this->getStaticAssets();
        $prefix = StaticAssets::PACKAGE_URL_PREFIX;

        $this->assertEquals('foo', $static->getPackageName($prefix . 'foo.js'));
        $this->assertEquals('bar', $static->getPackageName($prefix . 'bar.css'));

        $this->assertFalse($static->getPackageName($prefix));
        $this->assertFalse($static->getPackageName($prefix . '/bar'));
        $this->assertFalse($static->getPackageName('/statics/reset.css'));
        $this->assertFalse($static->getPackageName('/statics/head.load.min.js'));
    }

    public function testServePackages()
    {
        $static = $this->getStaticAssets();
        $prefix = StaticAssets::PACKAGE_URL_PREFIX;

        $request = Request::newFromPath($prefix . 'core,foo.js');
        $response = $this->app->getResponse();

        $this->assertTrue($static->serve($request));
        $this->assertStringContainsString('"head"', $response->getContent());
        $this->assertStringContainsString('jQuery.fn.foo=function(bar){return this.attr(bar)}', $response->getContent());
    }
}

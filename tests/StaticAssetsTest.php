<?php

/**
 * Set of unit tests for StaticAssets class
 *
 * $Id$
 */

class StaticAssetsTest extends PHPUnit_Framework_TestCase {

	private $app;

	public function setUp() {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$this->app = Nano::app($dir);

		// register a package
		$this->app->getConfig()->set('assets.packages', array(
			'core' => array(
				'js' => array(
					'/statics/head.load.min.js',
				),
			),
			'foo' => array(
				'js' => '/statics/jquery.foo.js',
				'css' => '/statics/reset.css',
				'deps' => 'core',
			),
			'jquery' => array(
				'js' => '/statics/jquery.foo.js',
				'ext' => array(
					'js' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'
				)
			)
		));
	}

	private function getStaticAssets() {
		// initialize static assets handler
		return $this->app->factory('StaticAssets');
	}

	public function testGetProcessor() {
		$static = $this->getStaticAssets();
		$this->assertInstanceOf('StaticAssets', $static);

		// processors
		$this->assertInstanceOf('StaticAssetsCss', $static->getProcessor('css'));
		$this->assertInstanceOf('StaticAssetsJs', $static->getProcessor('js'));
	}

	public function testServeTypeCheck() {
		$assets = array(
			// unsupported file types
			'/foo/bar' => false,
			'/test.xml' => false,
			'/package/app' => false,
			// correct file types
			'/statics/jquery.foo.js' => true,
			'/statics/reset.css' => true,
			'/statics/blank.gif' => true,
			'/statics/rss.png' => true,
			// package
			'/package/core.js' => true,
			'/package/foo.css' => true,
		);

		foreach($assets as $asset => $expected) {
			$request = Request::newFromPath($asset);
			$static = $this->getStaticAssets();
			$response = $this->app->getResponse();

			$this->assertEquals($expected, $static->serve($request));
			$this->assertEquals($expected ? Response::OK : Response::NOT_IMPLEMENTED, $response->getResponseCode());
		}
	}

	public function testPackageExists() {
		$static = $this->getStaticAssets();

		$this->assertTrue($static->packageExists('core'));
		$this->assertFalse($static->packageExists('fake'));
	}

	public function testFilterOutEmptyPackages() {
		$static = $this->getStaticAssets();

		$this->assertEquals(array('core'), $static->filterOutEmptyPackages(array('core'), 'js'));
		$this->assertEquals(array(), $static->filterOutEmptyPackages(array('core'), 'css'));

		$this->assertEquals(array('core', 'foo'), $static->filterOutEmptyPackages(array('core', 'foo'), 'js'));
		$this->assertEquals(array('foo'), $static->filterOutEmptyPackages(array('core', 'foo'), 'css'));

		$this->assertEquals(array('core', 'foo'), $static->filterOutEmptyPackages(array('core', 'foo', 'fake'), 'js'));
	}

	public function testResolveDependencies() {
		// fake packages config
		$packages = array(
			'core' => array(
				// no dependencies
			),
			'libFoo' => array(
				'deps' => 'core',
			),
			'libBar' => array(
				'deps' => 'libFoo',
			),
			'libTest' => array(
				'deps' => array('libBar', 'libFoo')
			)
		);
		$this->app->getConfig()->set('assets.packages', $packages);

		$static = $this->getStaticAssets();

		// fake package dependency
		$this->assertFalse($static->resolveDependencies(array('fake')));

		// single package dependencies
		$this->assertEquals(
			array('core'),
			$static->resolveDependencies(array('core'))
		);
		$this->assertEquals(
			array('core', 'libFoo'),
			$static->resolveDependencies(array('libFoo'))
		);
		$this->assertEquals(
			array('core', 'libFoo'),
			$static->resolveDependencies(array('libFoo'))
		);
		$this->assertEquals(
			array('core', 'libFoo', 'libBar', 'libTest'),
			$static->resolveDependencies(array('libTest'))
		);

		// multiple packages dependencies
		$this->assertEquals(
			array('core', 'libFoo', 'libBar'),
			$static->resolveDependencies(array('libFoo', 'libBar'))
		);
		$this->assertEquals(
			array('core', 'libFoo', 'libBar'),  // keep the order of dependencies
			$static->resolveDependencies(array('libBar', 'libFoo'))
		);
		$this->assertEquals(
			array('core', 'libFoo', 'libBar', 'libTest'),
			$static->resolveDependencies(array('libBar', 'libFoo', 'libTest'))
		);
	}

	public function testNotFoundAsset() {
		$assets = array(
			// existing file (and with cache buster)
			'/statics/rss.png' => true,
			'/r200/statics/rss.png' => true,
			// not existing file
			'/statics/404.js' => false,
			'/statics/not-existing.png' => false,
			// not existing package
			'/package/test.js' => false,
			'/package/not-existing.css' => false,
		);

		foreach($assets as $asset => $expected) {
			$request = Request::newFromPath($asset);
			$static = $this->getStaticAssets();
			$response = $this->app->getResponse();

			$this->assertEquals($expected, $static->serve($request));
			$this->assertEquals($expected ? Response::OK : Response::NOT_FOUND, $response->getResponseCode());
		}
	}

	public function testGetCacheBuster() {
		$static = $this->getStaticAssets();
		$cb = $this->app->getConfig()->get('assets.cb');

		$this->assertEquals($cb, $static->getCacheBuster());
	}

	public function testGetLocalPath() {
		$static = $this->getStaticAssets();
		$root = $this->app->getDirectory();

		$assets = array(
			'/statics/blank.gif' => $root . '/statics/blank.gif',
			'/statics/r300.gif' => $root . '/statics/r300.gif',
			'/release/blank.gif' => $root . '/release/blank.gif',
			'/r1/statics/blank.gif' => $root . '/statics/blank.gif',
			'/r200/statics/blank.gif' => $root . '/statics/blank.gif',
			'/r200/statics/r300.gif' => $root . '/statics/r300.gif',
		);

		foreach($assets as $path => $local) {
			$this->assertEquals($local, $static->getLocalPath($path));
		}
	}
	public function testGetUrlForAssetAndPackage() {
		// cache buster prepended (default behaviour)
		$static = $this->getStaticAssets();
		$cb = $static->getCacheBuster();

		$this->assertFalse($static->getCDNPath());

		$this->assertEquals("/site/r{$cb}/statics/jquery.foo.js", $static->getUrlForAsset('/statics/jquery.foo.js'));
		$this->assertEquals(array("/site/r{$cb}/package/core.js"), $static->getUrlsForPackage('core', 'js'));
		$this->assertEquals(array("/site/r{$cb}/package/foo.css"), $static->getUrlsForPackage('foo', 'css'));
		$this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));

		$this->assertEquals(array("/site/r{$cb}/package/core,foo.js"), $static->getUrlsForPackages(array('core', 'foo'), 'js'));

		// cache buster appended
		$this->app->getConfig()->set('assets.prependCacheBuster', false);

		$static = $this->getStaticAssets();
		$cb = $static->getCacheBuster();

		$this->assertEquals("/site/statics/jquery.foo.js?r={$cb}", $static->getUrlForAsset('/statics/jquery.foo.js'));
		$this->assertEquals(array("/site/package/core.js?r={$cb}"), $static->getUrlsForPackage('core', 'js'));
		$this->assertEquals(array("/site/package/foo.css?r={$cb}"), $static->getUrlsForPackage('foo', 'css'));
		$this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));
		$this->assertEquals(array("/site/package/core,foo.js?r={$cb}"), $static->getUrlsForPackages(array('core', 'foo'), 'js'));
	}

	public function testGetUrlForFile() {
		// cache buster prepended (default behaviour)
		$static = $this->getStaticAssets();
		$cb = $static->getCacheBuster();

		$root = $this->app->getDirectory();

		$this->assertFalse($static->getCDNPath());

		$this->assertEquals("/site/r{$cb}/statics/head.js", $static->getUrlForFile($root . '/statics/head.js'));
	}

	public function testGetUrlForAssetAndPackageWithCDN() {
		$cdnPath = 'http://cdn.net/sitepath';
		$this->app->getConfig()->set('assets.cdnPath', $cdnPath);

		$static = $this->getStaticAssets();
		$cb = $static->getCacheBuster();

		$this->assertEquals($cdnPath, $static->getCDNPath());

		$this->assertEquals("{$cdnPath}/r{$cb}/statics/jquery.foo.js", $static->getUrlForAsset('/statics/jquery.foo.js'));
		$this->assertEquals(array("{$cdnPath}/r{$cb}/package/core.js"), $static->getUrlsForPackage('core', 'js'));
		$this->assertEquals(array("{$cdnPath}/r{$cb}/package/foo.css"), $static->getUrlsForPackage('foo', 'css'));
		$this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));
		$this->assertEquals(array("{$cdnPath}/r{$cb}/package/core,foo.js"), $static->getUrlsForPackages(array('core', 'foo'), 'js'));

		// cache buster appended
		$this->app->getConfig()->set('assets.prependCacheBuster', false);

		$static = $this->getStaticAssets();
		$cb = $static->getCacheBuster();

		$this->assertEquals("{$cdnPath}/statics/jquery.foo.js?r={$cb}", $static->getUrlForAsset('/statics/jquery.foo.js'));
		$this->assertEquals(array("{$cdnPath}/package/core.js?r={$cb}"), $static->getUrlsForPackage('core', 'js'));
		$this->assertEquals(array("{$cdnPath}/package/foo.css?r={$cb}"), $static->getUrlsForPackage('foo', 'css'));
		$this->assertFalse($static->getUrlsForPackage('notExisting', 'css'));
		$this->assertEquals(array("{$cdnPath}/package/core,foo.js?r={$cb}"), $static->getUrlsForPackages(array('core', 'foo'), 'js'));
	}

	public function testGetUrlForExternalPackage() {
		// cache buster prepended (default behaviour)
		$static = $this->getStaticAssets();
		$cb = $static->getCacheBuster();

		$this->assertFalse($static->getCDNPath());

		$this->assertEquals(array(
			'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js',
			"/site/r{$cb}/package/jquery.js"
		), $static->getUrlsForPackage('jquery', 'js'));

	}

	public function testCssMinify() {
		$static = $this->getStaticAssets();
		$processor = $static->getProcessor('css');

		// original CSS => minifiied
		$css = array(
			'body, p {padding: 5px 0px; margin:  10px;}' => 'body,p{padding:5px 0;margin:10px}',
			'.foo .bar {padding: 10px 1px 0em 0.5em}' => '.foo .bar{padding:10px 1px 0 .5em}',
			'.foo > .bar {padding: 0.75em 0px;}' => '.foo > .bar{padding:.75em 0}',
			'mark    {background-color: #eeeeee; color: #333}' => 'mark{background-color:#eee;color:#333}',
			'.foo {padding: 0 0 15px 0px;}' => '.foo{padding:0 0 15px 0}',
			'.foo {margin: 0 0 25px;}' => '.foo{margin:0 0 25px}',
		);

		// temporary file to use for processing
		$file = Utils::getTempFile();

		foreach($css as $in => $out) {
			file_put_contents($file, $in);
			$this->assertEquals($out, $processor->processFiles(array($file)));
		}

		// clean up
		unlink($file);
	}

	public function testImageEncoding() {
		$dir = $this->app->getDirectory() . '/statics';
		$static = $this->getStaticAssets();
		$processor = $static->getProcessor('css');

		// encode existing image
		$this->assertContains('data:image/png;base64,', $processor->encodeImage($dir . '/rss.png'));

		// error handling
		$this->assertFalse($processor->encodeImage($dir . '/not-existing.png'));
		$this->assertFalse($processor->encodeImage($dir . '/file.xml'));
		$this->assertFalse($processor->encodeImage(__FILE__));

		// blank.gif embedding
		$this->assertEquals('data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw==', $processor->encodeImage($dir . '/blank.gif'));
		$this->assertContains('.foo{background-image:url(data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw==)}', $processor->processFiles(array($dir . '/blank.css')));

		// big files should not be encoded
		$this->assertContains('php-logo.jpg', $processor->processFiles(array($dir . '/php-logo.css')));
	}

	public function testCssInclude() {
		$dir = $this->app->getDirectory() . '/statics';
		$static = $this->getStaticAssets();
		$processor = $static->getProcessor('css');

		// include reset.css file
		$out = $processor->processFiles(array($dir . '/blank.css'));
		$this->assertNotContains('@import', $out);
		$this->assertContains('html,body,h1,h2,h3,h4,h5,h6', $out);
	}

	public function testJsMinify() {
		$dir = $this->app->getDirectory() . '/statics';
		$static = $this->getStaticAssets();
		$processor = $static->getProcessor('js');

		// min.js file should not be touched
		$this->assertEquals(file_get_contents($dir . '/head.load.min.js'), $processor->processFiles(array($dir . '/head.load.min.js')));

		// minify simple script
		$this->assertEquals('jQuery.fn.foo=function(bar){return this.attr(bar)}', $processor->processFiles(array($dir . '/jquery.foo.js')));
	}

	public function testGetPackageName() {
		$static = $this->getStaticAssets();
		$prefix = StaticAssets::PACKAGE_URL_PREFIX;

		$this->assertEquals('foo', $static->getPackageName($prefix . 'foo.js'));
		$this->assertEquals('bar', $static->getPackageName($prefix . 'bar.css'));

		$this->assertFalse($static->getPackageName($prefix));
		$this->assertFalse($static->getPackageName($prefix . '/bar'));
		$this->assertFalse($static->getPackageName('/statics/reset.css'));
		$this->assertFalse($static->getPackageName('/statics/head.load.min.js'));
	}

	public function testServePackages() {
		$static = $this->getStaticAssets();
		$prefix = StaticAssets::PACKAGE_URL_PREFIX;

		$request = Request::newFromPath($prefix . 'core,foo.js');
		$response = $this->app->getResponse();

		$this->assertTrue($static->serve($request));
		$this->assertContains('"head"', $response->getContent());
		$this->assertContains('jQuery.fn.foo=function(bar){return this.attr(bar)}', $response->getContent());
	}
}
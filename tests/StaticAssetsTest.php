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
	}

	private function getStaticAssets() {
		// initialize static assets handler
		return $this->app->factory('StaticAssets');;
	}

	public function testFactory() {
		$static = $this->getStaticAssets();
		$this->assertInstanceOf('StaticAssets', $static);

		// processors
		$this->assertInstanceOf('StaticAssetsCss', StaticAssets::factory('css'));
		$this->assertInstanceOf('StaticAssetsJs', StaticAssets::factory('js'));
	}

	public function testServeTypeCheck() {
		$assets = array(
			// unsupported file types
			'/foo/bar' => false,
			'/test.xml' => false,
			// correct file types
			'/statics/jquery.foo.js' => true,
			'/statics/reset.css' => true,
			'/statics/blank.gif' => true,
			'/statics/rss.png' => true,
		);

		foreach($assets as $asset => $expected) {
			$request = Request::newFromPath($asset);
			$static = $this->getStaticAssets();
			$response = $this->app->getResponse();

			$this->assertEquals($expected, $static->serve($request));
			$this->assertEquals($expected ? Response::OK : Response::NOT_IMPLEMENTED, $response->getResponseCode());
		}
	}

	public function testNotFoundAsset() {
		$assets = array(
			// existing file
			'/statics/rss.png' => true,
			// not existing file
			'/statics/404.js' => false,
			'/statics/not-existing.png' => false,
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

		$this->assertEquals("/site/r{$cb}/statics/jquery.foo.js", $static->getUrlForAsset('/statics/jquery.foo.js'));
		$this->assertEquals("/site/r{$cb}/package/app.js", $static->getUrlForPackage('app.js'));

		// cache buster appended
		$this->app->getConfig()->set('assets.prependCacheBuster', false);

		$static = $this->getStaticAssets();
		$cb = $static->getCacheBuster();

		$this->assertEquals("/site/statics/jquery.foo.js?r={$cb}", $static->getUrlForAsset('/statics/jquery.foo.js'));
		$this->assertEquals("/site/package/app.js?r={$cb}", $static->getUrlForPackage('app.js'));
	}

	public function testCssMinify() {
		$processor = StaticAssets::factory('css');

		// original CSS => minifiied
		$css = array(
			'body, p {padding: 5px 0px; margin:  10px;}' => 'body,p{padding:5px 0;margin:10px}',
			'.foo .bar {padding: 10px 1px 0em 0.5em}' => '.foo .bar{padding:10px 1px 0 .5em}',
			'.foo > .bar {padding: 0.75em 0px;}' => '.foo > .bar{padding:.75em 0}',
			'mark    {background-color: #eeeeee; color: #333}' => 'mark{background-color:#eee;color:#333}',
		);

		// temporary file to use for processing
		$file = Nano::getTempFile();

		foreach($css as $in => $out) {
			file_put_contents($file, $in);
			$this->assertEquals($out, $processor->process($file));
		}

		// clean up
		unlink($file);
	}

	public function testJsMinify() {
		$dir = $this->app->getDirectory() . '/statics';
		$processor = StaticAssets::factory('js');

		// min.js file should not be touched
		$this->assertEquals(file_get_contents($dir . '/head.load.min.js'), $processor->process($dir . '/head.load.min.js'));

		// minify simple script
		$this->assertEquals('jQuery.fn.foo=function(bar){return this.attr(bar)}', $processor->process($dir . '/jquery.foo.js'));
	}

	public function testImageEncoding() {
		$dir = $this->app->getDirectory() . '/statics';
		$processor = StaticAssets::factory('css');

		// encode existing image
		$this->assertContains('data:image/png;base64,', $processor->encodeImage($dir . '/rss.png'));

		// error handling
		$this->assertFalse($processor->encodeImage($dir . '/not-existing.png'));
		$this->assertFalse($processor->encodeImage($dir . '/file.xml'));
		$this->assertFalse($processor->encodeImage(__FILE__));

		// blank.gif embedding
		$this->assertEquals('data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw==', $processor->encodeImage($dir . '/blank.gif'));
		$this->assertContains('.foo{background-image:url(data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw==)}', $processor->process($dir . '/blank.css'));
	}

	public function testCssInclude() {
		$dir = $this->app->getDirectory() . '/statics';
		$processor = StaticAssets::factory('css');

		// include reset.css file
		$out = $processor->process($dir . '/blank.css');
		$this->assertNotContains('@import', $out);
		$this->assertContains('html,body,h1,h2,h3,h4,h5,h6', $out);
	}
}
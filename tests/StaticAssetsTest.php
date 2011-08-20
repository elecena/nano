<?php

/**
 * Set of unit tests for StaticAssets class
 *
 * $Id$
 */

class StaticAssetsTest extends PHPUnit_Framework_TestCase {

	private $app;

	private function getStaticAssets() {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$this->app = Nano::app($dir);

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
			'/statics/head.js' => true,
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
			'/statics/head.js' => true,
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
}
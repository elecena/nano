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
	}

	public function testServeTypeCheck() {
		$assets = array(
			'foo/bar' => false,
			'test.xml' => false,
			'statics/jquery.js' => true,
			'statics/reset.css' => true,
			'statics/logo.png' => true,
			'statics/logo.gif' => true,
			'statics/logo.jpg' => true,
		);

		foreach($assets as $asset => $expected) {
			$request = Request::newFromPath($asset);
			$static = $this->getStaticAssets();
			$response = $this->app->getResponse();

			$this->assertEquals($expected, $static->serve($request));
			$this->assertEquals($expected ? Response::OK : Response::NOT_IMPLEMENTED, $response->getResponseCode());
		}
	}
}
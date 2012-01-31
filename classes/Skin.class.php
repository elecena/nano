<?php

/**
 * Skin renderer
 *
 * Handles adding JS/CSS files and packages, setting page title, meta / link[rel] tags, adding global JS variables
 *
 * $Id$
 */

abstract class Skin {

	protected $app;

	// static assets handler
	protected $static;

	// template used to render the skin
	protected $template;

	// page title
	protected $pageTitle = '';

	// JS/CSS files and packages requested to be fetched
	protected $assets = array(
		'css' => array(),
		'js' => array(),
		'package' => array(),
	);

	// meta tags
	protected $meta = array();

	// link tags
	protected $links = array();

	// global JS variables
	protected $jsVariables = array();

	function __construct(NanoApp $app) {
		$this->app = $app;

		// setup objects
		$this->static = $this->app->factory('StaticAssets');
		$this->template = new Template(dirname(__FILE__));

		$config = $this->app->getConfig();
	}

	/**
	 * Add a given global JS variable
	 */
	function addJsVariable($name, $value) {
		$this->jsVariables[$name] = $value;
	}

	/**
	 * Add a given asset
	 */
	function addAsset($name, $type = false) {
		if ($type === false) {
			$dot = strrpos($name, '.');

			if ($dot !== false) {
				$ext = substr($name, $dot +1);
			}
			else {
				$ext = false;
			}

			switch($ext) {
				case 'css':
					$type = 'css';
					break;

				case 'js':
					$type = 'js';
					break;

				default:
					$type = 'package';
			}
		}

		if (isset($this->assets[$type])) {
			$this->assets[$type][] = $name;
		}
	}

}
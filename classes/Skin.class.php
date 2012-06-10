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
	protected $skinName;
	protected $skinDirectory;

	// static assets handler
	protected $staticAssets;

	// template used to render the skin
	protected $template;

	// page title
	protected $pageTitle = '';

	// JS/CSS files and packages requested to be fetched
	protected $assets = array(
		'css' => array(),
		'js' => array(),
	);

	protected $packages = array();

	// meta tags
	protected $meta = array();

	// link tags
	protected $links = array();

	// global JS variables
	protected $jsVariables = array();

	/**
	 * Return an instance of a given skin
	 */
	public static function factory(NanoApp $app, $skinName) {
		$skinDirectory = $app->getDirectory() . '/skins/' . strtolower($skinName);

		return Autoloader::factory('Skin', $skinName, $skinDirectory, array($app, $skinName));
	}

	/**
	 * Use Skin::factory
	 */
	function __construct(NanoApp $app, $skinName) {
		$this->app = $app;
		$this->skinName = $skinName;

		$this->app->getDebug()->log("Skin: using '{$this->skinName}'");

		$this->skinDirectory = $app->getDirectory() . '/skins/' . strtolower($skinName);

		// setup objects
		$this->staticAssets = $this->app->factory('StaticAssets');
		$this->template = new Template($this->skinDirectory . '/templates');
	}

	/**
	 * Get name of the skin
	 */
	function getName() {
		return $this->skinName;
	}

	/**
	 * Sets page title
	 */
	function setPageTitle($title) {
		$this->pageTitle = $title;
	}

	/**
	 * Add <meta> tag entry
	 */
	function addMeta($name, $value) {
		$this->meta[$name] = $value;
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
			$ext = ($dot !== false) ? substr($name, $dot +1) : false;

			switch($ext) {
				case 'css':
					$type = 'css';
					break;

				case 'js':
					$type = 'js';
					break;
			}
		}

		if (isset($this->assets[$type])) {
			$this->assets[$type][] = $name;
		}
	}

	/**
	 * Add a given assets package (and its dependencies)
	 */
	function addPackage($package) {
		$this->addPackages(array($package));
	}

	/**
	 * Add a given assets package (and its dependencies)
	 */
	function addPackages(Array $packages) {
		$this->packages += $packages;
	}

	/**
	 * Get skin data - variables available in skin template
	 */
	protected function getSkinData() {
		return array(
			// object instances
			'app' => $this->app,
			'router' => $this->app->getRouter(),
			'skin' => $this,
			'staticAssets' => $this->staticAssets,

			// additional data
			'skinPath' => $this->staticAssets->getUrlForFile($this->skinDirectory),
			'pageTitle' => $this->pageTitle,
			'renderTime' => $this->app->getResponse()->getResponseTime(),
		);
	}

	/**
	 * Renders set of <link> elements to be used to include CSS files
	 * requested via $skin->addPackage and $skin->addAsset
	 */
	function renderCssInclude($sep = "\n") {
		$urls = array();

		// packages
		$urls[] = $this->staticAssets->getUrlForPackages($this->packages, 'css');

		// single assets
		// TODO

		// render <link> elements
		$elements = array();

		foreach($urls as $url) {
			if ($url !== false) {
				$elements[] = '<link href="' . $url . '" rel="stylesheet" />';
			}
		}

		return implode($sep, $elements);
	}

	/**
	 * Renders set of <link> elements to be used to include CSS files
	 * requested via $skin->addPackage and $skin->addAsset
	 */
	function renderJsInclude($sep = "\n") {
		$urls = array();

		// packages
		$urls[] = $this->staticAssets->getUrlForPackages($this->packages, 'js');

		// single assets
		foreach($this->assets['js'] as $asset) {
			$urls[] = $this->staticAssets->getUrlForAsset($asset);
		}

		// render <link> elements
		$elements = array();

		foreach($urls as $url) {
			if ($url !== false) {
				$elements[] = '<script src="' . $url . '"></script>';
			}
		}

		return implode($sep, $elements);
	}

	/**
	 * Returns HTML of rendered page
	 */
	function render($content) {
		// set skin template's data
		$this->template->set($this->getSkinData());
		$this->template->set('content', $content);

		// render the skin and set the app response
		$html = $this->template->render('main');
		$this->app->getResponse()->setContent($html);
	}
}
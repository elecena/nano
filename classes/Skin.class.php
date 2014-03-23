<?php

use Nano\Template;

/**
 * Skin renderer
 *
 * Handles adding JS/CSS files and packages, setting page title, meta / link[rel] tags, adding global JS variables
 *
 * @property StaticAssets $staticAssets
 */
abstract class Skin {

	const SUBTITLE_SEPARATOR = ' &raquo; ';

	protected $app;
	protected $skinName;
	protected $skinDirectory;

	// static assets handler
	protected $staticAssets;

	// template used to render the skin
	protected $template;

	// page title
	protected $pageTitle = '';

	// page title parts
	protected $subTitles = array();

	// JS/CSS files and packages requested to be fetched
	protected $assets = array(
		'css' => array(),
		'js' => array(),
	);

	protected $packages = array();

	// meta and link tags in head section
	protected $meta = array();
	protected $link = array();

	// global JS variables
	protected $jsVariables = array();

	// skin will not wrap the content
	protected $bodyOnly = false;

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
	 * The skin will not be used to wrap content
	 */
	function setBodyOnly($val = true) {
		$this->bodyOnly = $val;
	}

	/**
	 * Sets page title (clears subtitles stack)
	 */
	function setPageTitle($title) {
		$this->pageTitle = $title;
		$this->subTitles = array();
	}

	/**
	 * Add page subtitle to the stack
	 *
	 * Example:
	 *  $skin->addPageSubtitle('search');
	 *  $skin->addPageSubtitle('foo');
	 */
	function addPageSubtitle($subtitle) {
		$this->subTitles[] = htmlspecialchars($subtitle);
	}

	/**
	 * Get page title
	 */
	function getPageTitle() {
		return !empty($this->subTitles) ? implode(self::SUBTITLE_SEPARATOR, array_reverse($this->subTitles)) : $this->pageTitle;
	}

	/**
	 * Add <meta> tag entry
	 */
	function addMeta($name, $value) {
		$this->meta[] = array(
			'name' => $name,
			'value' => $value
		);
	}

	/**
	 * Add <meta> OpenGraph entry
	 *
	 * @see http://ogp.me/
	 */
	function addOpenGraph($name, $value) {
		$this->meta[] = array(
			'property' => "og:{$name}",
			'content' => $value
		);
	}

	/**
	 * Add <link> tag entry
	 */
	function addLink($rel, $value) {
		$this->link[] = array(
			'rel' => $rel,
			'value' => $value
		);
	}

	/**
	 * Add RSS feed
	 */
	function addFeed($href, $title = false) {
		// <link rel="alternate" href="/feed/rss" type="application/rss+xml" title="RSS feed" />
		$this->link[] = array(
			'rel' => 'alternate',
			'href' => $href,
			'type' => 'application/rss+xml',
			'title' => $title,
		);
	}

	/**
	 * Set the canonical URL of the page
	 */
	function setCanonicalUrl($url) {
		$this->addLink('canonical', $url);
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
		$this->packages = array_merge($this->packages, $packages);
	}

	/**
	 * Return URL to a given local file
	 */
	function getUrlForAsset($asset) {
		return $this->staticAssets->getUrlForAsset($asset);
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
			'pageTitle' => $this->getPageTitle(),
		);
	}

	/**
	 * Returns list of URLs of a given type (CSS/JS) to be rendered by the skin
	 */
	protected function getAssetsUrls($type) {
		$urls = array();

		// packages
		$packagesUrls = $this->staticAssets->getUrlsForPackages($this->packages, $type);

		if ($packagesUrls !== false) {
			$urls = array_merge($urls, $packagesUrls);
		}
		
		// single assets
		foreach($this->assets[$type] as $asset) {
			$urls[] = $this->staticAssets->getUrlForAsset($asset);
		}

		return $urls;
	}

	/**
	 * Renders set of <meta> elements to be used in page's head section
	 */
	function renderHead($sep = "\n") {
		// render <meta> elements
		$elements = array();

		foreach($this->meta as $item) {
			$node = '<meta';

			foreach($item as $name => $value) {
				$value = htmlspecialchars($value);
				$node .= " {$name}=\"{$value}\"";
			}

			$node .= '>';

			$elements[] = $node;
		}

		// render <link> elements
		foreach($this->link as $item) {
			$node = '<link';

			foreach($item as $name => $value) {
				$value = htmlspecialchars($value);
				$node .= " {$name}=\"{$value}\"";
			}

			$node .= '>';

			$elements[] = $node;
		}

		// render global JS variables (wrapped in nano object)
		if (!empty($this->jsVariables)) {
			$elements[] = '<script>nano = ' . json_encode($this->jsVariables) . '</script>';
		}

		$this->app->getEvents()->fire('SkinRenderHead', array(&$elements));

		return rtrim($sep . implode($sep, $elements));
	}

	/**
	 * Renders set of <link> elements to be used to include CSS files
	 * requested via $skin->addPackage and $skin->addAsset
	 */
	function renderCssInclude($sep = "\n") {
		$urls = $this->getAssetsUrls('css');

		// render <link> elements
		$elements = array();

		foreach($urls as $url) {
			if ($url !== false) {
				$elements[] = '<link href="' . $url . '" rel="stylesheet">';
			}
		}

		$this->app->getEvents()->fire('SkinRenderCssInclude', array(&$elements));

		return rtrim($sep . implode($sep, $elements));
	}

	/**
	 * Renders set of <link> elements to be used to include CSS files
	 * requested via $skin->addPackage and $skin->addAsset
	 */
	function renderJsInclude($sep = "\n") {
		$urls = $this->getAssetsUrls('js');

		// render <link> elements
		$elements = array();

		foreach($urls as $url) {
			if ($url !== false) {
				$elements[] = '<script src="' . $url . '"></script>';
			}
		}

		$this->app->getEvents()->fire('SkinRenderJsInclude', array(&$elements));

		return implode($sep, $elements);
	}

	/**
	 * Returns HTML of rendered page
	 */
	function render($content) {
		// don't wrap the content
		if ($this->bodyOnly) {
			$this->app->getResponse()->setContent($content);
			return;
		}

		// set skin template's data
		$this->template->set($this->getSkinData());
		$this->template->set('content', $content);

		// render the head of the page
		echo $this->template->render('head');

		// flush the output
		#$this->app->getResponse()->flush();

		// render the rest of the page
		echo $this->template->render('main');
	}
}

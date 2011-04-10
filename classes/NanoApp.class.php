<?php

/**
 * Class for representing nanoPortal's application
 *
 * $Id$
 */

class NanoApp {
	// cache object
	protected $cache;

	// config
	protected $config;

	// debug logging
	protected $debug;

	// database connection
	protected $database;

	// events handler
	protected $events;

	// response
	protected $response;

	// HTTP request
	protected $request;

	// router
	protected $router;

	// an array of loaded modules
	protected $modules;

	// application's working directory
	protected $dir = '';

	// apllications' libraries directory
	protected $libraryDir = '';

	/**
	 * Create application based on given config
	 */
	function __construct($dir, $configSet = 'default') {
		$this->dir = realpath($dir);
		$this->libraryDir = $this->dir . '/lib';

		// register classes from /classes directory
		Autoloader::scanDirectory($this->dir. '/classes');

		// events handler
		$this->events = new Events();

		// debug
		$this->debug = new Debug($this->dir . '/log');

		// read configuration
		$this->config = new Config($this->dir . '/config');
		$this->config->load($configSet);

		// setup cache
		$cacheType = $this->config->get('cache.driver', 'file');
		$cacheOptions = $this->config->get('cache.options', array(
			'directory' => $this->dir . '/cache'
		));

		$this->cache = Cache::factory($cacheType, $cacheOptions);

		// set request
		$params = isset($_REQUEST) ? $_REQUEST : array();
		$env = isset($_SERVER) ? $_SERVER : array();

		$this->request = new Request($params, $env);

		// response
		$this->response = new Response();

		// set connection to database
		$this->database = Database::connect($this, $this->config->get('database', array()));

		// set private fields
		$this->router = new Router($this);

		// load and setup all modules
		$this->loadModules();
	}

	/**
	 * Load all modules
	 */
	private function loadModules() {
		$this->modules = array();
		$moduleFiles = glob($this->dir . '/modules/*');

		foreach($moduleFiles as $module) {
			$moduleName = ucfirst(basename($module));
			$this->modules[$moduleName] = Module::factory($moduleName, $this);
		}
	}

	/**
	 * Returns instance of given class from /classes directory
	 *
	 * Class constructor is provided with application's instance
	 */
	public function factory($className) {
		if (class_exists($className)) {
			$instance = new $className($this);
		}
		else {
			$instance = null;
		}

		return $instance;
	}

	/**
	 * Route given request
	 */
	public function route(Request $request) {
		// route given request
		$resp = $this->router->route($request);

		// wrap using Response object
		$this->response->setContent($resp);
	}

	/**
	 * Return an instance of given module
	 */
	public function getModule($moduleName) {
		$instance = isset($this->modules[$moduleName]) ? $this->modules[$moduleName] : null;

		return $instance;
	}

	/**
	 * Return list of names of loaded modules
	 */
	public function getModules() {
		return array_keys($this->modules);
	}

	/**
	 * Return path to application
	 */
	public function getDirectory() {
		return $this->dir;
	}

	/**
	 * Return path to nanoPortal libraries
	 */
	public function getLibDirectory() {
		return $this->libraryDir;
	}

	/**
	 * Add given library to include_path
	 */
	public function addLibrary($directory) {
		// normalize path
		$fullPath = $this->getLibDirectory() . '/' . $directory;

		// update include_path
		set_include_path(get_include_path() . PATH_SEPARATOR . $fullPath);
	}

	/**
	 * Return cache
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * Return config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Return database
	 */
	public function getDatabase() {
		return $this->database;
	}

	/**
	 * Return debug
	 */
	public function getDebug() {
		return $this->debug;
	}

	/**
	 * Return events
	 */
	public function getEvents() {
		return $this->events;
	}

	/**
	 * Return response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Return request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Return router
	 */
	public function getRouter() {
		return $this->router;
	}
}
<?php

/**
 * Class for representing nanoPortal's application
 *
 * @property Cache $cache
 * @property Database $database
 * @property Skin $skin
 */
class NanoApp {
	// cache object
	protected $cache;

	// config
	protected $config;

	// debug logging
	protected $debug;

	// database connection
	protected $database = false;

	// events handler
	protected $events;

	// response
	protected $response;

	// HTTP request
	protected $request;

	// router
	protected $router;

	// skin
	protected $skin = false;

	// an array of loaded modules
	protected $modules;

	// application's working directory
	protected $dir = '';

	// apllications' libraries directory
	protected $libraryDir = '';

	// objects instances used by getInstance()
	protected $instances = array();

	// current application instance
	protected static $app;

	/**
	 * Return the current instance of NanoApp
	 *
	 * @return NanoApp
	 */
	public static function app() {
		if (is_null(self::$app)) {
			throw new Exception('Instance of NanoApp not registered');
		}

		return self::$app;
	}

	/**
	 * Create application based on given config
	 */
	function __construct($dir, $configSet = 'default', $logFile = 'debug') {
		// register the current application instance
		self::$app = $this;

		$this->dir = realpath($dir);
		$this->libraryDir = $this->dir . '/lib';

		// register classes from /classes directory
		Autoloader::scanDirectory($this->dir. '/classes');

		// events handler
		$this->events = new Events($this);

		// read configuration
		$this->config = new Config($this->dir . '/config');
		$this->config->load($configSet);

		// debug
		$this->debug = new Debug($this->dir . '/log', $logFile);

		if ($this->config->get('debug.enabled', false)) {
			$this->debug->enableLog();
			$this->debug->clearLogFile();

			// log nano version and when app was started
			$this->debug->log('Nano v' . Nano::VERSION . ' started at ' . date('Y-m-d H:i:s'));
			$this->debug->log('----');
		}

		// setup cache (using default driver if none provided)
		$cacheSettings = $this->config->get('cache', array(
			'driver' => 'file',
		));

		$this->cache = Cache::factory($this, $cacheSettings);

		// set request
		$params = isset($_REQUEST) ? $_REQUEST : array();
		$env = isset($_SERVER) ? $_SERVER : array();

		$this->request = new Request($params, $env);
		if (!$this->request->isCLI()) {
			$this->debug->log("Request: {$this->request->getPath()} from {$this->request->getIP()}");
		}

		// response
		$this->response = new Response($this, $env);

		// requests router
		$this->router = new Router($this);

		$this->debug->log('----');
	}

	/**
	 * Send an event informing that application has finished its work
	 */
	public function tearDown() {
		$this->debug->log();

		// stats
		// TODO: static
		$stats = \Nano\Stats::getCollector($this, 'request');
		$stats->increment('requests.count');

		if ($this->request->isApi()) {
			$stats->increment('type.api');
		}
		else if (!$this->request->isInternal() && !$this->request->isCLI()) {
			$stats->increment('type.main');
		}

		$this->events->fire('NanoAppTearDown', array($this));

		$this->debug->log('----');
		$this->debug->log('Script is completed');
	}

	/**
	 * Checks whether given file / directory is inside application's directory (security stuff!)
	 */
	public function isInAppDirectory($resource) {
		$resource = realpath($resource);

		return strpos($resource, $this->getDirectory()) === 0;
	}

	/**
	 * Returns instance of given class from /classes directory
	 *
	 * Class constructor is provided with application's instance and with extra parameters (if provided)
	 *
	 * NanoApp::factory() will ALWAYS return fresh class instance
	 *
	 * @deprecated create instances using new operator
	 */
	public function factory($className, Array $params = array()) {
		if (class_exists($className)) {
			// add NanoApp instance as the first constructor parameter
			array_unshift($params, $this);

			// http://www.php.net/manual/en/function.call-user-func-array.php#74427
			// make a reflection object
			$reflectionObj = new ReflectionClass($className);

			// use Reflection to create a new instance, using the $args (since PHP 5.1.3)
			$instance = $reflectionObj->newInstanceArgs($params);
		}
		else {
			$instance = null;
		}

		return $instance;
	}

	/**
	 * Returns instance of given class from /classes directory
	 *
	 * NanoApp::getInstance() follows singleton pattern
	 *
	 * @deprecated
	 */
	public function getInstance($className) {
		if (!isset($this->instances[$className])) {
			$this->instances[$className] = $this->factory($className);
		}

		return $this->instances[$className];
	}

	/**
	 * Return fresh instance of a given controller
	 */
	public function getController($controllerName) {
		return Controller::factory($this, $controllerName);
	}

	/**
	 * Internally route the request and return raw data (array) or an Output object wrapping the response
	 */
	protected function route(Request $request) {
		// route given request
		$resp = $this->router->route($request);

		// $resp can be either raw data (array) or Output object wrapping the response from the module
		return $resp;
	}

	/**
	 * Dispatch given request
	 *
	 * Returns raw data returned by the module
	 */
	public function dispatchRequest(Request $request) {
		// route given request
		$resp = $this->route($request);

		// $resp can be either raw data (array) or Output object wrapping the response from the module
		if ($resp instanceof Output) {
			$resp = $resp->getData();
		}

		return $resp;
	}

	/**
	 * Dispatch request given by the controller and method name (and optional parameters)
	 *
	 * Returns raw data returned by the module
	 */
	public function dispatch($controllerName, $methodName = '', Array $params = array()) {
		$request = Request::newFromControllerName($controllerName, $methodName, $params, Request::INTERNAL);

		// route given request
		return $this->dispatchRequest($request);
	}

	/**
	 * Render the results of given request
	 *
	 * Returns template's output for data returned by the module
	 */
	public function renderRequest(Request $request) {
		$resp = $this->route($request);

		if ($resp instanceof Output) {
			// module returned wrapped data
			$resp = $resp->render();
		}

		return $resp;
	}

	/**
	 * Render the results of request given by the controller and method name (and optional parameters)
	 *
	 * Returns template's output for data returned by the module
	 */
	public function render($controllerName, $methodName = '', Array $params = array()) {
		$request = Request::newFromControllerName($controllerName, $methodName, $params, Request::INTERNAL);

		// render given request
		return $this->renderRequest($request);
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
		// lazy connection handling
		if ($this->database === false) {
			// set connection to database (using db.default config entry)
			$this->database = Database::connect($this);
		}

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

	/**
	 * Return skin
	 */
	public function getSkin() {
		// lazy connection handling
		if ($this->skin === false) {
			// use the default skin
			$skinName = $this->config->get('skin', 'default');

			// allow to override the default choice
			$this->events->fire('NanoAppGetSkin', array($this, &$skinName));

			// create a instance of a skin
			$this->skin = Skin::factory($this, $skinName);
		}

		return $this->skin;
	}
}
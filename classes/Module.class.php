<?php

/**
 * Abstract class for representing nanoPortal's modules
 *
 * $Id$
 */

abstract class Module {
	// application
	protected $app;

	// module's directory
	protected $dir;

	// module's name
	protected $name;

	// output's format
	protected $format;

	// module's data
	protected $data;

	/**
	 * Setup the module usin ggiven application
	 */
	protected function __construct(NanoApp $app, $name) {
		$this->name = $name;
		$this->data = array();

		// set reference to the application
		$this->app = $app;
	}

	/**
	 * Lazy loading of application objects
	 *
	 * Get them from NanoApp instance when needed
	 */
	public function __get($name) {
		$methodName = 'get' . ucfirst($name);

		if (method_exists($this->app, $methodName)) {
			return $this->app->$methodName();
		}
	}

	/**
	 * Create and setup instance of given module for given application
	 */
	public static function factory(NanoApp $app, $moduleName) {
		$className = $moduleName . 'Module';

		// request given file
		$dir = $app->getDirectory() . '/modules/' . strtolower($moduleName);
		$src = $dir . '/' . $className . '.class.php';

		if (file_exists($src)) {
			require_once $src;
			$instance = new $className($app, $moduleName);
			$instance->dir = $dir;
		}
		else {
			$instance = null;
		}

		return $instance;
	}

	/**
	 * Clean up the module before routing the request
	 */
	public function clearState() {
		$this->setFormat(null);
		$this->data = array();
	}

	/**
	 * Use provided request
	 */
	public function setRequest(Request $request) {
		$this->request = $request;

		// default API response format
		if ($this->request->isAPI()) {
			$this->setFormat('json');
		}
	}

	/**
	 * Get module's directory
	 */
	public function getDirectory() {
		return $this->dir;
	}

	/**
	 * Get module's template
	 */
	public function getTemplate() {
		return new Template($this->getDirectory() . '/templates');
	}

	/**
	 * Set output's format
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * Get output's format
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Set module's data to be passed to the template or formatted by the Router
	 */
	protected function set($key, $val = null) {
		// key/value array can be provided set more entries
		if (is_array($key)) {
			$this->data = array_merge($this->data, $key);
		}
		else if (!is_null($val)) {
			$this->data[$key] = $val;
		}
	}

	/**
	 * Set module's data using automagical feature of PHP
	 *
	 * Example: $this->itemId = 123;
	 */
	public function __set($key , $val) {
		$this->data[$key] = $val;
	}

	/**
	 * Get module's data
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Render current module data to HTML using provided template
	 */
	public function render($templateName) {
		$template = $this->getTemplate();
		$template->set($this->getData());

		return $template->render($templateName);
	}

	/**
	 * Binds given module's method to be fired when given event occurs
	 *
	 * When can false is returned, fire() method returns false too and no callbacks execution is stopped
	 */
	protected function bind($eventName, $callbackMethod) {
		$this->events->bind($eventName, array($this, $callbackMethod));
	}

	/**
	 * Execute all callbacks binded to given event (passing additional parameters if provided)
	 */
	protected function fire($eventName, $params = array()) {
		return $this->events->fire($eventName, $params);
	}
}
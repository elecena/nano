<?php

/**
 * Abstract class for representing nanoPortal's controllers
 *
 * $Id$
 */

abstract class Controller {
	// application
	protected $app;

	// controller's directory
	protected $dir;

	// HTTP request
	protected $request;

	// response
	protected $response;

	// view
	protected $view;

	// controller's name
	protected $name;

	// output's format
	protected $format;

	// controller's data
	protected $data;

	/**
	 * Setup the controller usin ggiven application
	 */
	protected function __construct(NanoApp $app, $name) {
		$this->name = $name;
		$this->data = array();

		// set reference to the application
		$this->app = $app;

		$this->request = $app->getRequest();
		$this->response = $app->getResponse();
	}

	/**
	 * Get controller's directory
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Create and setup instance of given controller for given application
	 */
	public static function factory(NanoApp $app, $controllerName) {
		$className = ucfirst(strtolower($controllerName)) . 'Controller';

		// request given file
		$dir = $app->getDirectory() . '/controllers/' . strtolower($controllerName);
		$src = $dir . '/' . $className . '.class.php';

		if (file_exists($src)) {
			require_once $src;
			$instance = new $className($app, $controllerName);
			$instance->dir = $dir;
		}
		else {
			$instance = null;
		}

		return $instance;
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
	 * Use provided view
	 */
	public function setView(View $view) {
		$this->view = $view;
	}

	/**
	 * Get controller's directory
	 */
	public function getDirectory() {
		return $this->dir;
	}

	/**
	 * Get controller's template
	 */
	public function getTemplate() {
		return $this->view->getTemplate();
	}

	/**
	 * Set output's format
	 */
	public function setFormat($format) {
		$this->view->setFormat($format);
	}

	/**
	 * Get output's format
	 */
	public function getFormat() {
		return $this->view->getFormat();
	}

	/**
	 * Set controller's data to be passed to the template or formatted by the Router
	 */
	protected function set($key, $val) {
		$this->data[$key] = $val;
	}

	/**
	 * Set controller's data using automagical feature of PHP
	 *
	 * Example: $this->itemId = 123;
	 */
	public function __set($key , $val) {
		$this->data[$key] = $val;
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
		else if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		else {
			return null;
		}
	}

	/**
	 * Check if given controller data entry exists
	 */
	public function __isset($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Remove controller data entry
	 */
	public function __unset($key) {
		unset($this->data[$key]);
	}

	/**
	 * Set controller's data to be passed to the template or formatted by the Router
	 */
	protected function setData(Array $data) {
		$this->data = $data;
	}

	/**
	 * Get controller's data
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Render current controller data to HTML using provided template
	 */
	public function render($templateName) {
		$template = $this->getTemplate();
		$template->set($this->getData());

		return $template->render($templateName);
	}

	/**
	 * Binds given controller's method to be fired when given event occurs
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
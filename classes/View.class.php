<?php

/**
 * View class wrapping response from the controller
 *
 * $Id$
 */

class View {

	// application
	private $app;

	// controller called
	private $controller;

	// method called
	private $method;

	// template
	private $template;

	// template name
	private $templateName;

	// output's format
	protected $format;

	// controller's data
	protected $data = array();

	public function __construct(NanoApp $app, Controller $controller) {
		$this->app = $app;
		$this->controller = $controller;

		$this->template = new Template($this->controller->getDirectory() . '/templates');
	}

	public function setTemplateName($templateName) {
		$this->templateName = $templateName;
	}

	public function getTemplate() {
		return $this->template;
	}

	public function getController() {
		return $this->controller;
	}

	public function getControllerName() {
		return $this->controller->getName();
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function getMethod() {
		return $this->method;
	}

	public function setFormat($format) {
		$this->format = $format;
	}

	public function getFormat() {
		return $this->format;
	}

	public function setData(Array $data) {
		$this->data = $data;
	}

	public function getData() {
		return $this->data;
	}

	public function render() {

	}
}
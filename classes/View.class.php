<?php

/**
 * View class wrapping response from the controller
 *
 * $Id$
 */

class View {

	// application
	private $app;

	// template
	private $template;

	// output's format
	protected $format;

	// controller's data
	protected $data = array();

	public function __construct(NanoApp $app, Controller $controller) {
		$this->app = $app;
		$this->template = new Template($controller->getDirectory() . '/templates');
	}
}
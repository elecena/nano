<?php

/**
 * Handles HTML templates
 *
 * $Id$
 */

class Template {
	// directory where template files are stored
	private $dir = '';

	// variables to be available in the template
	private $vars = array();

	/**
	 * Handle templates from given directory
	 */
	function __construct($dir) {
		$this->dir = realpath($dir);
	}

	/**
	 * Add given variable(s) to the template
	 */
	public function set($name, $value = null) {
		if (is_array($name)) {
			// set mutiple variables (key / value set)
			$this->vars = array_merge($this->vars, $name);
		}
		else {
			// set single variable
			$this->vars[$name] = $value;
		}
	}

	/**
	 * Render given template
	 */
	public function render($templateName) {
		$templateFile = $this->dir . '/' . basename($templateName) . '.tmpl.php';

		// check if given template file exists
		if (!file_exists($templateFile)) {
			die(__METHOD__ . ": file <strong>{$templateFile}</strong> not found!");
		}

		// extract template's variables into current scope
		extract($this->vars);

		// render template
		ob_start();
		include $templateFile;
		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}
}

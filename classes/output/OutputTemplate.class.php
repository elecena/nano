<?php

/**
 * Data with HTML template renderer
 *
 * $Id$
 */

class OutputTemplate extends Output {

	private $template;
	private $templateName;

	/**
	 * Set template object to be rendered
	 */
	public function setTemplate(Template $template) {
		$this->template = $template;
	}

	/**
	 * Set template file to be rendered
	 */
	public function setTemplateName($templateName) {
		$this->templateName = $templateName;
	}

	/**
	 * Render current data
	 */
	public function render() {
		if(!empty($this->template)) {
			$this->template->set($this->getData());
			$ret = $this->template->render($this->templateName);
		}
		else {
			$ret = false;
		}

		return $ret;
	}

	/**
	 * @see http://www.ietf.org/rfc/rfc2854.txt
	 */
	public function getContentType() {
		return 'text/html';
	}
}
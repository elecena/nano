<?php

/**
 * XML renderer
 *
 * @see http://stackoverflow.com/questions/1397036/how-to-convert-array-to-simplexml-in-php
 * @see http://stackoverflow.com/questions/99350/php-associative-arrays-to-and-from-xml
 *
 * $Id$
 */

class OutputXml extends Output {

	/**
	 * Converts given array into XML and adds nodes into given element
	 */
	private function arrayToXml(Array &$data, SimpleXMLElement $node) {
		foreach($data as $key => $val) {
			if (is_array($val)) {
				$this->arrayToXml($val, $node->addChild($key));
			}
			else {
				if (is_numeric($key)) {
					$node->addChild('value', $val);
				}
				else {
					$node->addChild($key, $val);
				}
			}
		}
	}

	/**
	 * Render current data
	 */
	public function render() {
		$this->xml = new SimpleXMLElement('<root />');

		// recursively add data to XML
		$this->arrayToXml($this->data, $this->xml);

		// render XML
		return trim($this->xml->asXML());
	}

	/**
	 * @see http://www.ietf.org/rfc/rfc3023.txt
	 */
	public function getContentType() {
		return 'text/xml; charset=UTF-8';
	}
}
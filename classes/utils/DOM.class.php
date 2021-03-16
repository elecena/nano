<?php

/**
 * Helper class for parsing XML/HTML and performing XPath queries
 */

class DOM {

	// charset used by source XML/HTML
	private $charset;

	// SimpleXMLElement of loaded node
	private $doc;

	function __construct(SimpleXMLElement $doc) {
		$this->doc = $doc;
	}

	/**
	 * Parse provided XML into DOM
	 */
	public static function newFromXml($xml, $strictMode = false) {
		$instance = null;

		libxml_use_internal_errors(true);

		try {
			// (try to) parse as XML
			$doc = new SimpleXMLElement($xml);
			$instance = new self($doc);
			$instance->charset = 'utf-8';
		}
		catch(Exception $e) {
			// fallback to HTML (if not in strict mode)
			if (!$strictMode) {
				$instance = self::newFromHtml($xml);
			}
		}

		libxml_use_internal_errors(false);

		return $instance;
	}

	/**
	 * Parse provided HTML into DOM and perform charset conversion to utf
	 */
	public static function newFromHtml($html, $charset = null) {
		$instance = null;

		// force charset
		if (is_string($charset)) {
			$html = iconv($charset, 'utf-8', $html);
		}
		// perform encoding detection
		else if (preg_match('#<meta[^>]+content=[^>]+;\s?(charset=)?([A-Za-z0-9-]+)#i', $html, $matches)) {
			if (!empty($matches[2])) {
				$charset = strtolower(trim($matches[2]));

				// convert to utf
				if ($charset != 'utf-8') {
					$html = iconv($charset, 'utf-8', $html);
				}
			}
		}

		$html = strtr($html, array(
			'&nbsp;' => ' ',
			"\r\n" => "\n",
		));

		// parse given HTML using utf-8 charset
		// @see http://pl2.php.net/manual/pl/domdocument.loadhtml.php#52251
		$html = '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' . $html;

		// parse using DOMDocument (suppress HTML warnings)
		$doc = new DOMDocument();
		@$doc->loadHTML($html);

		if (!empty($doc)) {
			$xml = simplexml_import_dom($doc);

			if (!empty($xml)) {
				$instance = new self($xml);
				$instance->charset = $charset;
			}
		}

		return $instance;
	}

	/**
	 * Return a set of elements matching given XPath
	 *
	 * @return SimpleXMLElement[]
	 */
	public function xpath($xpath) {
		return $this->doc->xpath($xpath);
	}

	/**
	 * Return the first node matching given XPath
	 */
	public function getNode($xpath) {
		$nodes = $this->xpath($xpath);

		return !empty($nodes) ? $nodes[0] : null;
	}

	/**
	 * Return content of the first node matching given XPath
	 */
	public function getNodeContent($xpath) {
		$node = $this->getNode($xpath);

		return !is_null($node) ? (string) $node : null;
	}

	/**
	 * Return text content (tags are stripped out) of the first node matching given XPath
	 */
	public function getNodeTextContent($xpath) {
		$node = $this->getNode($xpath);

		return !is_null($node) ? strip_tags($node->asXML()) : null;
	}

	/**
	 * Return given attribute of the first node matching given XPath
	 */
	public function getNodeAttr($xpath, $attr) {
		$node = $this->getNode($xpath);

		return !is_null($node[$attr]) ? (string) $node[$attr] : null;
	}

	/**
	 * Remove the first node matching given XPath
	 */
	public function removeNode($xpath) {
		$node = $this->getNode($xpath);

		if (!is_null($node)) {
			$dom = dom_import_simplexml($node);
			$dom->parentNode->removeChild($dom);

			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Return charset used by source XML/HTML
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Return XML string for current DOM
	 */
	public function __toString() {
		return $this->doc->asXML();
	}
}
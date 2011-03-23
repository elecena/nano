<?php

/**
 * Helper class for parsing XML/HTML and performing XPath queries
 *
 * $Id$
 */

class DOM {

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
	public static function newFromHtml($html, $charset = false) {
		$instance = null;

		// force charset
		if (is_string($charset)) {
			$html = iconv($charset, 'utf-8', $html);
		}
		// perform encoding detection
		else if (preg_match('#<meta[^>]+content=[^>]+charset=([A-Za-z0-9-]+)#i', $html, $matches)) {
			if (!empty($matches[1])) {
				$charset = strtolower(trim($matches[1]));

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
		$doc = @DOMDocument::loadHTML($html);

		if (!empty($doc)) {
			$xml = simplexml_import_dom($doc);

			if (!empty($xml)) {
				$instance = new self($xml);
			}
		}

		return $instance;
	}

	/**
	 * Return XML string for current DOM
	 */
	public function __toString() {
		return $this->doc->asXML();
	}
}
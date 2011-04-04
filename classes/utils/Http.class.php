<?php

/**
 * Class with helper methods for HttpClient
 *
 * $Id$
 */

class Http {

	/**
	 * Send GET HTTP request for a given URL
	 */
	static public function get($url, $query = array()) {
		$client = new HttpClient();

		return $client->get($url, $query);
	}

	/**
	 * Send POST HTTP request for a given URL
	 */
	static public function post($url, $fields = array()) {
		$client = new HttpClient();

		return $client->post($url, $fields);
	}

	/**
	 * Send HEAD HTTP request for a given URL
	 */
	static public function head($url, $query = array()) {
		$client = new HttpClient();

		return $client->head($url, $query);
	}
}
<?php

/**
 * Class with helper methods for HttpClient
 */
class Http {

	/**
	 * Send GET HTTP request for a given URL
	 *
	 * @param string $url
	 * @param array $query
	 * @return \Nano\Http\Response
	 */
	static public function get($url, $query = []) {
		$client = new HttpClient();
		$ret = $client->get($url, $query);

		$client->close();
		return $ret;
	}

	/**
	 * Send POST HTTP request for a given URL
	 *
	 * @param string $url
	 * @param array $fields
	 * @return \Nano\Http\Response
	 */
	static public function post($url, $fields = []) {
		$client = new HttpClient();
		$ret = $client->post($url, $fields);

		$client->close();
		return $ret;
	}

	/**
	 * Send HEAD HTTP request for a given URL
	 *
	 * @param string $url
	 * @param array $query
	 * @return \Nano\Http\Response
	 */
	static public function head($url, $query = []) {
		$client = new HttpClient();
		$ret = $client->head($url, $query);

		$client->close();
		return $ret;
	}
}
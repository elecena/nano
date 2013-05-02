<?php

/**
 * Helper class for sending page views / events to Google Analytics server-side
 *
 * @see http://qvister.se/2010/02/02/server-side-google-analytics/
 * @see http://github.com/mptre/php-ga
 */

class GAClient {

	const URL = 'http://www.google-analytics.com/__utm.gif';
	const USER_AGENT = 'Mozilla/5.0 (Windows NT 5.1; rv:13.0) Gecko/20100101 Firefox/13.0';
	const VERSION = '4.4sh';

	/* @var $http HttpClient */
	private $http;
	private $debug;

	// tracker data
	private $accountId;
	private $ip;
	private $visitorId;

	function __construct(NanoApp $app) {
		$this->debug = $app->getDebug();

		$this->http = $app->factory('HttpClient');
		$this->http->setUserAgent(self::USER_AGENT);

		$this->setIP('89.248.165.13');
		$this->visitorId = $this->generateVisitorId();
	}

	/**
	 * Send request to Google Analytics
	 */
	private function track(Array $params = array()) {
		// @see http://code.google.com/intl/pl/apis/analytics/docs/tracking/gaTrackingTroubleshooting.html#gifParameters
		$defaultParams = array(
			'utmip' => $this->ip,								// client's IP
			'utmhn' => '',										// host
			'utmr' => '-',										// referrer
			'utmp' => '/',										// page path
			'utmac' => $this->accountId,						// GA account ID
			'utmwv' => self::VERSION,							// tracker's version
			'utmn' => $this->rand(),							// cache buster
			'utmvid' => $this->visitorId,						// visitor unique ID
			'utmcc' => '__utma=999.999.999.999.999.1;',			// cookies
		);

		$params = array_merge($defaultParams, $params);
		ksort($params);

		// set request header
		$this->http->setRequestHeader('Accept-Language', 'en');

		$res = $this->http->get(self::URL, $params);
		return $res->getResponseCode() === 200;
	}

	/**
	 * Generate unique ID
	 */
	private function rand() {
		return rand(0, 0x7fffffff);
	}

	/**
	 * Generate visitor unique ID
	 */
	private function generateVisitorId() {
		$id = md5( uniqid($this->rand(), true) );
		return '0x' . substr($id, 0, 16);
	}

	/**
	 * Set "fake" visitor IP
	 */
	public function setIP($ip) {
		$this->debug->log(__METHOD__ . ": {$ip}");
		$this->ip = $ip;
	}

	/**
	 * Set GA account ID
	 */
	public function setAccountId($accountId) {
		// change account ID to be "mobile" account
		$this->accountId = str_replace('UA-', 'MO-', $accountId);

		$this->debug->log(__METHOD__ . ": {$this->accountId}");
	}

	/**
	 * Track page view
	 *
	 * @param $url string page URL
	 * @return bool
	 */
	public function trackPageview($url) {
		$this->debug->log(__METHOD__ . ": {$url}");

		return $this->track(array(
			'utmp' => $url,
		));
	}

	/**
	 * Track an event
	 *
	 * @see http://code.google.com/intl/pl/apis/analytics/docs/tracking/eventTrackerGuide.html
	 *
	 * @param string $category ex. "video"
	 * @param null|string $action ex. "play"
	 * @param null|string $label ex. "foo bar"
	 * @param null|string $val ex. 42
	 * @return bool
	 */
	public function trackEvent($category, $action = null, $label = null, $val = null) {
		// 5(stats*pageload*time)(2800)
		$args = array($category);

		if ($action != null) {
			$args[] = $action;
		}

		if ($label != null) {
			$args[] = $label;
		}

		$event = "5(" . implode('*', $args) . ")";

		if (is_numeric($val)) {
			$event .= "({$val})";
		}

		$this->debug->log(__METHOD__ . ": {$event}");

		return $this->track(array(
			'utmp' => '/',
			'utmt' => 'event',
			'utme' => $event,
		));
	}
}
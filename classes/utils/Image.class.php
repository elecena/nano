<?php

/**
 * Helper class for handling image manipulations
 *
 * @see http://www.php.net/manual/en/class.imagick.php
 *
 * $Id$
 */

abstract class Image {
	// image handler from GD / Imagick
	protected $img;

	// image type
	protected $type;

	// image dimensions
	protected $width;
	protected $height;

	/**
	 * Create an instance of Image for given raw image data
	 */
	abstract function __construct($raw);

	/**
	 * Create an instance of Image from given URL (file will be fetched via HTTP)
	 */
	public static function newFromUrl($url) {
		$raw = Http::get($url);

		return self::newFromRaw($raw);
	}

	/**
	 * Create an instance of Image from given file
	 */
	public static function newFromFile($file) {
		$raw = file_get_contents($file);

		return self::newFromRaw($raw);
	}

	/**
	 * Create an instance of Image from raw image data
	 */
	public static function newFromRaw($raw) {
		if (!empty($raw)) {
			try {
				return self::getInstance($raw);
			}
			catch(Exception $e) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Returns an instance of proper image driver
	 *
	 * Auto-detects which library to use: Imagick or GD
	 */
	private static function getInstance($raw) {
		// TODO: Imagick
		if (false) {
			$driverName = 'ImageImagick';
		}
		// fallback to GD
		else if (function_exists('gd_info')){
			$driverName = 'ImageGD';
		}
		else {
			return false;
		}

		return new $driverName($raw);
	}

	/**
	 * Scale an image to fit given box
	 */
	abstract public function scale($width, $height) ;

	/**
	 * Crop an image to fit given box
	 */
	abstract public function crop($width, $height);

	/**
	 * Return image raw data
	 */
	abstract public function render($type, $quality = false);

	/**
	 * Save image raw data to a given file
	 */
	public function save($filename, $type, $quality = false) {
		$raw = $this->render($type, $quality);

		return !empty($raw) && file_put_contents($filename, $raw) !== false;
	}

	public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}

	public function getType() {
		return $this->type;
	}

	public function getMimeType() {
		// Note from PHP manual: this function does not require the GD image library.
		return image_type_to_mime_type($this->type);
	}
}
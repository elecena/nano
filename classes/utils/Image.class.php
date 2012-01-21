<?php

/**
 * Helper class for handling image manipulations
 *
 * $Id$
 */

class Image {

	// image handler from GD
	private $img;

	// image type
	private $type;

	// image dimensions
	private $width;
	private $height;

	function __construct($img) {
		$this->img = $img;

		if (is_resource($this->img)) {
			$this->width = imagesx($this->img);
			$this->height = imagesy($this->img);
			#$this->type = imagesy($this->img);
		}
	}

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
			$img = imagecreatefromstring($raw);

			if (is_resource($img)) {
				return new self($img);
			}
		}

		return false;
	}

	/**
	 * Scale an image to fit given box
	 */
	public function scale($width, $height) {
		// calculate scale-down ratio
		$ratio = min($width / $this->width, $height / $this->height);

		// don't scale up
		if ($ratio >= 1) {
			return false;
		}

		// new dimensions
		$width = round($this->width * $ratio);
		$height = round($this->height * $ratio);

		// create new image
		$thumb = ImageCreateTrueColor($width, $height);

		if (!is_resource($thumb)) {
			return false;
		}

		// paste rescaled image
		// @see http://pl.php.net/imageCopyResampled
		$res = imageCopyResampled(
			// destination
			$thumb,
			// source
			$this->img,
			// destination upper left coordinates
			0, 0,
			// source upper left coordinates
			0, 0,
			// destination dimensions
			$width, $height,
			// source dimensions
			$this->width, $this->height
		);

		if ($res === false) {
			return true;
		}

		// free memory
		imagedestroy($this->img);

		// update image data
		$this->img = $thumb;
		$this->width = $width;
		$this->height = $height;

		return true;
	}

	/**
	 * Crop an image to fit given box
	 */
	public function crop($width, $height) {
		// calculate scale-down ratio
		$ratio = max($width / $this->width, $height / $this->height);

		// don't scale up
		if ($ratio >= 1) {
			return false;
		}

		// create new image
		$thumb = ImageCreateTrueColor($width, $height);

		if (!is_resource($thumb)) {
			return false;
		}

		// new dimensions
		#$width = round($this->width * $ratio);
		#$height = round($this->height * $ratio);

		// 500x300 (src) -> 333x200
		// 200x200 (dest)
		//  ratio = 2/3
		// dst_x = 333 - 200 / 2 -> ((500 x 2/3) - 200) / 2
		// dst_y = 200 - 200 / 2 -> ((200 x 2/3) - 200) / 2

		// paste rescaled image
		// @see http://pl.php.net/imageCopyResampled
		$res = imageCopyResampled(
			// destination
			$thumb,
			// source
			$this->img,
			// destination upper left coordinates
			round(($this->width * $ratio - $width) / 2) * -1,
			round(($this->height * $ratio - $height) / 2) * -1,
			// source upper left coordinates
			0, 0,
			// destination dimensions
			round($this->width * $ratio), round($this->height * $ratio),
			// source dimensions
			$this->width, $this->height
		);

		if ($res === false) {
			return true;
		}

		// free memory
		imagedestroy($this->img);

		// update image data
		$this->img = $thumb;
		$this->width = $width;
		$this->height = $height;

		return true;
	}

	/**
	 * Return image raw data
	 */
	public function render($type, $quality = false) {
		ob_start();

		switch($type) {
			case 'jpeg':
				imagejpeg($this->img, null, $quality ? $quality : 75);
				break;

			case 'gif':
				imagegif($this->img);
				break;

			case 'png':
				imagepng($this->img, null, $quality ? $quality : 9);
				break;

			default:
				return false;
		}

		// get an image
		$raw = ob_get_contents();
		ob_end_clean();

		if (!empty($raw)) {
			$this->type = $type;
		}

		return $raw;
	}

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
		return image_type_to_mime_type($this->type);
	}
}
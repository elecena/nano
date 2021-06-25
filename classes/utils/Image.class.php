<?php

/**
 * Helper class for handling image manipulations
  *
 * $Id$
 */

abstract class Image
{
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
    abstract public function __construct(string $raw);

    /**
     * Create an instance of Image from given URL (file will be fetched via HTTP)
     *
     * @throws Nano\Http\ResponseException
     */
    public static function newFromUrl(string $url): Image
    {
        $raw = Http::get($url);

        return self::newFromRaw($raw);
    }

    /**
     * Create an instance of Image from given file
     */
    public static function newFromFile(string $file): Image
    {
        $raw = file_get_contents($file);

        return self::newFromRaw($raw);
    }

    /**
     * Create an instance of Image from raw image data
     *
     * @throws RuntimeException
     */
    public static function newFromRaw(string $raw): Image
    {
        return self::getInstance($raw);
    }

    /**
     * Returns an instance of proper image driver
     *
     * Auto-detects which library to use: Imagick or GD
     *
     * @throws RuntimeException
     */
    private static function getInstance(string $raw): Image
    {
        /**
         * @see ImageGDTest::setUp
         */
        global $NANO_FORCE_GD;

        // Image Magick
        if (class_exists('Imagick') && empty($NANO_FORCE_GD)) {
            $driverName = ImageImagick::class;
        }
        // fallback to GD
        elseif (function_exists('gd_info')) {
            $driverName = ImageGD::class;
        } else {
            throw new RuntimeException('No image driver available');
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
    abstract public function render($type, int $quality = 75);

    /**
     * Save image raw data to a given file
     */
    public function save($filename, $type, int $quality = 75)
    {
        $raw = $this->render($type, $quality);

        if (empty($raw)) {
            return false;
        }

        return file_put_contents($filename, $raw);
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        // Note from PHP manual: this function does not require the GD image library.
        return image_type_to_mime_type($this->type);
    }
}

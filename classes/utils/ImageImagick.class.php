<?php

/**
 * Helper class for handling image manipulations using ImageMagick library
 *
 * @see http://www.php.net/manual/en/class.imagick.php
 */

class ImageImagick extends Image
{

    /**
     * Create an instance of Image for given raw image data
     */
    public function __construct($raw)
    {
        $this->img = new Imagick();
        $res = $this->img->readImageBlob($raw);

        if ($res !== true) {
            throw new Exception('Imagick::readImageBlob() failed!');
        }

        $this->width = $this->img->getImageWidth();
        $this->height = $this->img->getImageHeight();
    }

    /**
     * Scale an image to fit given box and keeping proportions
     */
    public function scale($width, $height)
    {
        // calculate new dimension
        // @see http://www.php.net/manual/en/imagick.scaleimage.php#93667
        $ratio = min($width / $this->width, $height / $this->height);

        // don't scale up
        if ($ratio > 1) {
            return false;
        }

        $res = $this->img->scaleImage($this->width * $ratio, $this->height * $ratio, false /* exact scaling */);

        if ($res !== true) {
            return false;
        }

        // update image data
        $this->width = $this->img->getImageWidth();
        $this->height = $this->img->getImageHeight();

        return true;
    }

    /**
     * Crop an image to fit given box
     */
    public function crop($width, $height)
    {
        // calculate scale-down ratio
        $ratio = max($width / $this->width, $height / $this->height);

        // don't scale up
        if ($ratio > 1) {
            return false;
        }

        // scale down (if needed)
        if ($ratio < 1) {
            $res = $this->scale($this->width * $ratio, $this->height * $ratio);

            if ($res !== true) {
                return false;
            }
        }

        // and crop
        $res = $this->img->extentImage(
            // dimensions
            $width,
            $height,
            // destination upper left coordinates
            round(($this->width - $width) / 2),
            round(($this->height - $height) / 2)
        );

        if ($res !== true) {
            return false;
        }

        // update image data
        $this->width = $this->img->getImageWidth();
        $this->height = $this->img->getImageHeight();

        return true;
    }

    /**
     * Return image raw data
     */
    public function render($type, $quality = false)
    {
        switch ($type) {
            case 'jpeg':
                $type = IMAGETYPE_JPEG;
                $this->img->setImageFormat('jpeg');
                $this->img->setImageCompressionQuality($quality ? $quality : 75);
                break;

            case 'gif':
                $type = IMAGETYPE_GIF;
                $this->img->setImageFormat('gif');
                break;

            case 'png':
                $type = IMAGETYPE_PNG;
                $this->img->setImageFormat('png');
                break;

            default:
                return false;
        }

        // get an image
        $raw = $this->img->getImageBlob();

        if (!empty($raw)) {
            $this->type = $type;
        }

        return $raw;
    }
}

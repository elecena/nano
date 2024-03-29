<?php

/**
 * Helper class for handling image manipulations using GD library
 *
 * @see http://www.php.net/manual/en/book.image.php
 */

class ImageGD extends Image
{
    /**
     * Create an instance of Image for given raw image data
     *
     * @throws Exception
     */
    public function __construct(string $raw)
    {
        $this->img = @imagecreatefromstring($raw);

        if ($this->img === false) {
            throw new Exception('imagecreatefromstring() failed! Got from imagecreatefromstring(): ' . print_r($this->img, true));
        }

        $this->width = imagesx($this->img);
        $this->height = imagesy($this->img);
    }

    /**
     * Scale an image to fit given box
     */
    public function scale($width, $height)
    {
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
        $thumb = imagecreatetruecolor($width, $height);

        if ($thumb === false) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        // paste rescaled image
        // @see http://pl.php.net/imageCopyResampled
        $res = imageCopyResampled(
            // destination
            $thumb,
            // source
            $this->img,
            // destination upper left coordinates
            0,
            0,
            // source upper left coordinates
            0,
            0,
            // destination dimensions
            $width,
            $height,
            // source dimensions
            $this->width,
            $this->height
        );

        if ($res === false) {
            // @codeCoverageIgnoreStart
            return true;
            // @codeCoverageIgnoreEnd
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
    public function crop($width, $height)
    {
        // calculate scale-down ratio
        $ratio = max($width / $this->width, $height / $this->height);

        // don't scale up
        if ($ratio > 1) {
            return false;
        }

        // create new image
        $thumb = imagecreatetruecolor($width, $height);

        if ($thumb === false) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

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
            0,
            0,
            // destination dimensions
            round($this->width * $ratio),
            round($this->height * $ratio),
            // source dimensions
            $this->width,
            $this->height
        );

        if ($res === false) {
            // @codeCoverageIgnoreStart
            return true;
            // @codeCoverageIgnoreEnd
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
    public function render($type, int $quality = 75)
    {
        ob_start();

        switch ($type) {
            case 'jpeg':
                $type = IMAGETYPE_JPEG;
                imagejpeg($this->img, null, $quality);
                break;

            case 'gif':
                $type = IMAGETYPE_GIF;
                imagegif($this->img);
                break;

            case 'png':
                $type = IMAGETYPE_PNG;
                imagepng($this->img, null, min($quality, 9));
                break;

            default:
                ob_end_clean();
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
}

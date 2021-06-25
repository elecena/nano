<?php

/**
 * Set of unit tests for Image class (for Imagick)
 *
 * @covers ImageImagick
 */
class ImageImagickTest extends ImageTestBase
{
    public function setUp(): void
    {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('imagick extension not installed');
        }

        parent::setUp();
    }

    public function testNewFromRawThrowsAnException()
    {
        $this->expectExceptionMessage('imagecreatefromstring() failed');
        Image::newFromRaw('An invalid RAW image');
    }

}

<?php

/**
 * Set of unit tests for Image class (for GD)
 *
 * @covers ImageGD
 */
class ImageGDTest extends ImageTestBase
{
    public function tearDown(): void
    {
        unset($GLOBALS['NANO_FORCE_GD']);
    }

    public function setUp(): void
    {
        /**
         * @see Image::getInstance
         */
        global $NANO_FORCE_GD;
        $NANO_FORCE_GD = true;

        if (!function_exists('gd_info')) {
            $this->markTestSkipped('gd extension not installed');
        }

        parent::setUp();
    }

    public function testNewFromRawThrowsAnException()
    {
        $this->expectExceptionMessage('imagecreatefromstring() failed');
        Image::newFromRaw('An invalid RAW image');
    }
}

<?php

/**
 * Set of unit tests for Image class
 *
 * $Id$
 */

class ImageTest extends PHPUnit_Framework_TestCase {

	const DEBUG = false;

	public function setUp() {
		// 578x406
		$this->file = dirname(__FILE__) . '/app/statics/php-logo.jpg';
	}

	public function testNewFromFile() {
		$img = Image::newFromFile($this->file);

		$this->assertInstanceOf('Image', $img);
		$this->assertEquals(578, $img->getWidth());
		$this->assertEquals(406, $img->getHeight());
	}

	public function testScale() {
		$img = Image::newFromFile($this->file);

		$this->assertInstanceOf('Image', $img);

		if (self::DEBUG) $img->save('img.jpg', 'jpeg');

		// scaling up no permitted
		$this->assertFalse($img->scale(600, 500));
		$this->assertFalse($img->scale(580, 450));

		// scale down
		$this->assertTrue($img->scale(300, 100));

		$this->assertEquals(142, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());

		// render an image
		$this->assertFalse($img->render('tiff'));
		$this->assertTrue($img->render('jpeg') !== false);
		$this->assertEquals(IMAGETYPE_JPEG, $img->getType());
		$this->assertEquals('image/jpeg', $img->getMimeType());

		if (self::DEBUG) $img->save('img-scaled.jpg', 'jpeg');

		// scale down to 300x410 bounding box
		$img = Image::newFromFile($this->file);
		$this->assertTrue($img->scale(300, 410));

		$this->assertEquals(300, $img->getWidth());
		$this->assertEquals(210, $img->getHeight());

		if (self::DEBUG) $img->save('img-scaled-bounding.jpg', 'jpeg');
	}

	public function testCrop() {
		$img = Image::newFromFile($this->file);

		$this->assertInstanceOf('Image', $img);

		// scaling up no permitted
		$this->assertFalse($img->crop(600, 500));

		// crop (leave just the middle part)
		$this->assertTrue($img->crop(578, 250));
		if (self::DEBUG) $img->save('img-crop1a.jpg', 'jpeg');

		$this->assertEquals(578, $img->getWidth());
		$this->assertEquals(250, $img->getHeight());

		// crop (leave just the middle part)
		$this->assertTrue($img->crop(450, 250));
		if (self::DEBUG) $img->save('img-crop1b.jpg', 'jpeg');

		$this->assertEquals(450, $img->getWidth());
		$this->assertEquals(250, $img->getHeight());
	}

	public function testCropAndResize() {
		$img = Image::newFromFile($this->file);

		$this->assertInstanceOf('Image', $img);

		// crop
		$this->assertTrue($img->crop(300, 100));

		if (self::DEBUG) $img->save('img-crop2.jpg', 'jpeg');

		$this->assertEquals(300, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());
	}
}
<?php

/**
 * Set of unit tests for Image class
 *
 * $Id$
 */

class ImageTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
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

		#$img->save('img.jpg', 'jpeg');

		// scaling up no permitted
		$this->assertFalse($img->scale(600, 500));
		$this->assertFalse($img->scale(578, 406));

		// scale down
		$this->assertTrue($img->scale(300, 100));

		$this->assertEquals(142, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());

		// render an image
		$this->assertFalse($img->render('tiff'));
		$this->assertTrue($img->render('jpeg') !== false);
		$this->assertEquals(IMAGETYPE_JPEG, $img->getType());
		$this->assertEquals('image/jpeg', $img->getMimeType());

		#$img->save('img-scaled.jpg', 'jpeg');
	}

	public function testCrop() {
		$img = Image::newFromFile($this->file);

		$this->assertInstanceOf('Image', $img);

		// scaling up no permitted
		$this->assertFalse($img->crop(600, 500));
		$this->assertFalse($img->crop(578, 406));

		// scale down
		$this->assertTrue($img->crop(300, 100));

		$this->assertEquals(300, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());

		#$img->save('img-crop.jpg', 'jpeg');
	}
}
<?php

/**
 * Set of unit tests for Image class
 */

abstract class ImageTestBase extends \Nano\NanoBaseTest
{

    /* @var string $file */
    private $file;

    const DEBUG = false;

    public function setUp(): void
    {
        // 578x406
        $this->file = __DIR__ . '/app/statics/php-logo.jpg';
    }

    public function testNewFromFile()
    {
        $img = Image::newFromFile($this->file);

        $this->assertInstanceOf(Image::class, $img);
        $this->assertEquals(578, $img->getWidth());
        $this->assertEquals(406, $img->getHeight());
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(string $typeToRender, int $expectedType, string $expectMimeType)
    {
        $img = Image::newFromFile($this->file);

        $this->assertNotFalse($img->render($typeToRender));
        $this->assertEquals($expectedType, $img->getType());
        $this->assertEquals($expectMimeType, $img->getMimeType());
    }

    public function renderDataProvider(): Generator
    {
        yield 'jpeg' => [ 'jpeg', IMAGETYPE_JPEG, 'image/jpeg'];
        yield 'png' => [ 'png', IMAGETYPE_PNG, 'image/png'];
        yield 'gif' => [ 'gif', IMAGETYPE_GIF, 'image/gif'];
    }

    public function testScale()
    {
        $img = Image::newFromFile($this->file);

        $this->assertInstanceOf(Image::class, $img);

        if (self::DEBUG) {
            $img->save('img.jpg', 'jpeg');
        }

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

        if (self::DEBUG) {
            $img->save('img-scaled.jpg', 'jpeg');
        }

        // scale down to 300x410 bounding box
        $img = Image::newFromFile($this->file);
        $this->assertTrue($img->scale(300, 410));

        $this->assertEquals(300, $img->getWidth());
        $this->assertEqualsWithDelta(211, $img->getHeight(), 2, 'This image should be 211 px high');

        if (self::DEBUG) {
            $img->save('img-scaled-bounding.jpg', 'jpeg');
        }
    }

    public function testCrop()
    {
        $img = Image::newFromFile($this->file);

        $this->assertInstanceOf(Image::class, $img);

        // scaling up no permitted
        $this->assertFalse($img->crop(600, 500));

        // crop (leave just the middle part)
        $this->assertTrue($img->crop(578, 250));
        if (self::DEBUG) {
            $img->save('img-crop1a.jpg', 'jpeg');
        }

        $this->assertEquals(578, $img->getWidth());
        $this->assertEquals(250, $img->getHeight());

        // crop (leave just the middle part)
        $this->assertTrue($img->crop(450, 250));
        if (self::DEBUG) {
            $img->save('img-crop1b.jpg', 'jpeg');
        }

        $this->assertEquals(450, $img->getWidth());
        $this->assertEquals(250, $img->getHeight());
    }

    public function testCropAndResize()
    {
        $img = Image::newFromFile($this->file);

        $this->assertInstanceOf(Image::class, $img);

        // crop
        $this->assertTrue($img->crop(300, 100));

        if (self::DEBUG) {
            $img->save('img-crop2.jpg', 'jpeg');
        }

        $this->assertEquals(300, $img->getWidth());
        $this->assertEquals(100, $img->getHeight());
    }
}

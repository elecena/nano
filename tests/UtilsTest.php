<?php

/**
 * Set of unit tests for Utils class
 */

class UtilsTest extends \Nano\NanoBaseTest
{
    public function testGetTempFile()
    {
        $content = 'foobar';
        $tmp = Utils::getTempFile();

        $this->assertFileExists($tmp);

        file_put_contents($tmp, $content);
        $this->assertEquals($content, file_get_contents($tmp));

        unlink($tmp);
        $this->assertFalse(file_exists($tmp));
    }
}

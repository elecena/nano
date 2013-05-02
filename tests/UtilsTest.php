<?php

/**
 * Set of unit tests for Utils class
 */

class UtilsTest extends PHPUnit_Framework_TestCase {

	public function testGetTempFile() {
		$content = 'foobar';
		$tmp = Utils::getTempFile();

		$this->assertFileExists($tmp);

		file_put_contents($tmp, $content);
		$this->assertEquals($content, file_get_contents($tmp));

		unlink($tmp);
		$this->assertFalse(file_exists($tmp));
	}

	public function testCreatePidFile() {
		$pidFile = Utils::getTempFile();
		$pid = getmypid();

		Utils::createPidFile($pidFile);

		$this->assertEquals("{$pid}\n", file_get_contents($pidFile));

		unlink($pidFile);
		$this->assertFalse(file_exists($pidFile));
	}

	public function testBase62Encoding() {
		$hash = sha1('foobar') . md5('foobar');
		$value = base_convert($hash, 16, 10);

		$encoded = Utils::baseEncode($value);
		$this->assertEquals(Utils::baseDecode($encoded), $value);
	}
}
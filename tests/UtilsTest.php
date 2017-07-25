<?php

/**
 * Set of unit tests for Utils class
 */

class UtilsTest extends \Nano\NanoBaseTest {

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
}
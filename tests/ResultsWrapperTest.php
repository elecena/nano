<?php

/**
 * Set of unit tests for ResultsWrapper class
 *
 * $Id$
 */

class ResultsWrapperTest extends PHPUnit_Framework_TestCase {

	public function testSetGet() {
		$data = array(
			'foo' => 'bar',
			'id' => 123,
		);

		$res = new ResultsWrapper($data);
		$res->set('test', 'abc');

		$this->assertEquals($data['foo'], $res->getFoo());
		$this->assertEquals($data['id'], $res->getId());
		$this->assertEquals('abc', $res->getTest());
		
		$res = new ResultsWrapper();
		$res->set('foo', 'test');

		$this->assertEquals('test', $res->getFoo());
	}
}
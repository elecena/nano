<?php

/**
 * Set of unit tests for Pagination class
 *
 * $Id$
 */

class PaginationTest extends PHPUnit_Framework_TestCase {

	private $app;

	public function setUp() {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$this->app = Nano::app($dir);
	}

	private function getPager() {
		$pager = new Pagination($this->app);

		$pager->setUrl('foo', 'bar', array('q' => 'foo', 'p' => Pagination::PAGE));
		$pager->setPerPageLimit(25);
		$pager->setCount(125);

		return $pager;
	}

	public function testPager() {
		$pager = $this->getPager();

		$this->assertEquals(1, $pager->getFirstPage());
		$this->assertEquals(5, $pager->getLastPage());
	}

	public function testFirstPage() {
		$pager = $this->getPager();

		$pager->setCurrentPage(1);
		$this->assertEquals(1, $pager->getCurrentPage());
		$this->assertTrue($pager->isFirstPage());
		$this->assertFalse($pager->getPrevPage());
		$this->assertEquals(2, $pager->getNextPage());
	}

	public function testLastPage() {
		$pager = $this->getPager();
		$pager->setCurrentPage(5);
		$this->assertTrue($pager->isLastPage());
		$this->assertFalse($pager->getNextPage());
		$this->assertEquals(4, $pager->getPrevPage());
	}

	public function testOutOfRangePages() {
		$pager = $this->getPager();

		$pager->setCurrentPage(0);
		$this->assertEquals($pager->getFirstPage(), $pager->getCurrentPage());

		$pager->setCurrentPage(10);
		$this->assertEquals($pager->getLastPage(), $pager->getCurrentPage());
	}

	public function testPagesUrl() {
		$pager = $this->getPager();
		$pager->setCurrentPage(1);

		$this->assertContains('/foo/bar?q=foo&p=1', $pager->getUrlForPage(1));
		$this->assertContains('/foo/bar?q=foo&p=1', $pager->getUrlForFirstPage());
		$this->assertContains('/foo/bar?q=foo&p=5', $pager->getUrlForLastPage());
	}

	public function testGetItems() {
		$pager = $this->getPager();

		$pager->setCurrentPage(3);
		$items = $pager->getItems(); var_dump($items);

		$pager->setCurrentPage(1);
		$items = $pager->getItems(); var_dump($items);

		$pager->setCurrentPage(5);
		$items = $pager->getItems(); var_dump($items);
	}
}
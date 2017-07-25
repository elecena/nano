<?php

/**
 * An example model
 */
class TestModel extends Model {

	/**
	 * @param NanoApp $app
	 */
	public function __construct(NanoApp $app) {
		parent::__construct($app);

		$this->data = array(
			'foo' => 'bar',
		);
	}
}

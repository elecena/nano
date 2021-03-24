<?php

namespace Nano\TestApp;

/**
 * An example model
 *
 * @method array getFoo
 */
class TestModel extends \Model
{
    public function __construct()
    {
        parent::__construct();

        $this->data = [
            'foo' => 'bar',
        ];
    }
}

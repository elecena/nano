<?php

namespace Nano\TestApp;

use Model;

/**
 * An example model
 *
 * @method getFoo() array
 */
class TestModel extends Model
{
    public function __construct()
    {
        parent::__construct();

        $this->data = [
            'foo' => 'bar',
        ];
    }
}

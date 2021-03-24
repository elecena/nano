<?php

namespace Tests\App\Models;

use Model;

/**
 * An example model
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

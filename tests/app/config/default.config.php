<?php

$config['test'] = 'value';
$config['home'] = 'http://example.org/site/';
$config['index'] = '/foo/index';
$config['skin'] = 'TestApp'; /* @see SkinTestApp */
$config['cache'] = [
    'driver' => 'file',
];
$config['db'] = [
    'default' => [
        'driver' => 'mysql',
        'utf' => true,
    ],
];
$config['foo']['bar'] = '123';
$config['assets'] = [
    'cb' => 123,
];

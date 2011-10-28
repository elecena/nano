<?php

$config['test'] = 'value';
$config['home'] = 'http://example.org/site/';
$config['index'] = '/foo/index';
$config['cache'] = array(
	'driver' => 'file',
);
$config['db'] = array(
	'default' => array(
		'driver' => 'mysql',
		'utf' => true,
	)
);
$config['foo']['bar'] = '123';
$config['assets'] = array(
	'cb' => 123,
);
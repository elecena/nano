<?php

$finder = PhpCsFixer\Finder::create()
  ->in(__DIR__ . '/app/')
  ->in(__DIR__ . '/classes/')
  ->in(__DIR__ . '/tests/')
;

$config = new PhpCsFixer\Config();

// https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/rules/index.rst
// https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/ruleSets/index.rst
return $config
  ->setRules([
      '@PSR2' => true,
      'array_syntax' => ['syntax' => 'short'],
      'list_syntax' => true,
      'ternary_to_null_coalescing' => true,
      'trailing_comma_in_multiline' => true,
  ])
  ->setFinder($finder)
;

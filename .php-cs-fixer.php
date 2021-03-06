<?php

$finder = PhpCsFixer\Finder::create()
  ->in(__DIR__ . '/app/')
  ->in(__DIR__ . '/classes/')
  ->in(__DIR__ . '/tests/')
;

$config = new PhpCsFixer\Config();

return $config
  ->setRules([
      '@PSR2' => true,
      'array_syntax' => ['syntax' => 'short'],
  ])
  ->setFinder($finder)
;


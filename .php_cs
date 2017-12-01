<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in('src');

return Config::create()
    ->setFinder($finder)
    ->setRules([
      '@Symfony' => true,
      'array_syntax' => ['syntax' => 'short'],
      'blank_line_before_return' => true,
      'ordered_imports' => true,
      'phpdoc_add_missing_param_annotation' => true,
      'phpdoc_order' => true,
    ])
    ->setUsingCache(true);

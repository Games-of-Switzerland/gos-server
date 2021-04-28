<?php

use drupol\PhpCsFixerConfigsDrupal\Config\Drupal8;

$finder = PhpCsFixer\Finder::create()
  ->in(['web/modules/custom'])
  ->name('*.module')
  ->name('*.inc')
  ->name('*.install')
  ->name('*.test')
  ->name('*.profile')
  ->name('*.theme')
  ->notPath('*.md')
  ->notPath('*.info.yml')
;

$config = Drupal8::create()
  ->setFinder($finder)
;

$rules = $config->getRules();
$rules['global_namespace_import'] = FALSE;
$rules['php_unit_set_up_tear_down_visibility'] = FALSE;
$config->setRules($rules);
return $config;

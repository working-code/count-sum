<?php

declare(strict_types=1);

use PhpCsFixer\Config;

$finder =
    PhpCsFixer\Finder::create()
        ->in([
            __DIR__ . '/../../src',
            __DIR__ . '/../../tests/Unit',
        ]);

return (new Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PhpCsFixer' => true,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'concat_space' => ['spacing' => 'one'],
        'simplified_null_return' => false,
        'increment_style' => false,
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder($finder);

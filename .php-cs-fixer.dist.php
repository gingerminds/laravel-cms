<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/routes',
        __DIR__ . '/database',
        __DIR__ . '/src',
    ])
    ->name('*.php')
    ->notName('*.blade.php');

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'strict_param' => true,
        'single_quote' => true,
        'binary_operator_spaces' => ['default' => 'align_single_space_minimal'],
        'ternary_operator_spaces' => true,
        'no_trailing_whitespace' => true,
        'no_extra_blank_lines' => true,
        'concat_space' => ['spacing' => 'one'],
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');

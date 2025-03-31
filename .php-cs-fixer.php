<?php

$finder = \PhpCsFixer\Finder::create()->in([
    './examples',
    './src',
    './tests',
]);

return new \PhpCsFixer\Config()->setFinder($finder)->setRules([
    '@Symfony' => true,
    'class_attributes_separation' => [
        'elements' => [
            'case' => 'none',
            'const' => 'one',
            'property' => 'one',
            'trait_import' => 'none',
        ],
    ],
    'no_blank_lines_after_class_opening' => false,
    'no_extra_blank_lines' => [
        'tokens' => [
            'attribute',
            'case',
            'continue',
            'default',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'switch',
            'throw',
            'use',
        ],
    ],
    'ordered_types' => [
        'null_adjustment' => 'always_first',
    ],
]);

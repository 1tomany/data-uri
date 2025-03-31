<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;

$finder = PhpCsFixer\Finder::create()
    ->in('./src')
    // ->exclude('folder-to-exclude') // Optional: exclude folders
    // ->notPath('file-to-exclude.php') // Optional: exclude specific files
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'class_attributes_separation' => [
            'elements' => [
            // 'const' => 'one', 'method' => 'one', 'property' => 'one', 'trait_import' => 'none', 'case' => 'none',
            ],
        ],
        'no_extra_blank_lines' => [
            'tokens' => [
                'attribute',
                'case',
                'continue',
                // 'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],


        // '@PER-CS2.0' => true,
        'no_blank_lines_after_class_opening' => false,




        'ordered_types' => [
            'null_adjustment' => 'always_first',
        ],
    ])
    ->setFinder($finder)
;

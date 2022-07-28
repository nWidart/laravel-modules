<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    '@PSR12' => true,
        // Concatenation should be used with at least one whitespace around.
        'concat_space' => ['spacing' => 'one'],
        // Unused use statements must be removed.
        'ordered_imports' => true,
        // Removes extra empty lines.
        'no_extra_blank_lines' => true,
        // An empty line feed should precede a return statement.
        'blank_line_before_statement' => true,
        // Unused use statements must be removed.
        'no_unused_imports' => true,
        // Remove trailing whitespace at the end of blank lines.
        'no_whitespace_in_blank_line' => true,
        // There MUST be one blank line after the namespace declaration.
        'blank_line_after_namespace' => true,
        // There should be exactly one blank line before a namespace declaration.
        'single_blank_line_before_namespace' => true,
        // Each namespace use MUST go on its own line and there MUST be one blank line after the use statements block.
        'single_line_after_imports' => true,
        // Ensure there is no code on the same line as the PHP open tag and it is followed by a blankline.
        'blank_line_after_opening_tag' => true,
        // Remove duplicated semicolons.
        'no_empty_statement' => true,
        // PHP multi-line arrays should have a trailing comma.
        'trailing_comma_in_multiline' => true,
        // There should be no empty lines after class opening brace.
        'no_blank_lines_after_class_opening' => true,
        // There should not be blank lines between docblock and the documented element.
        'no_blank_lines_after_phpdoc' => true,
        // Phpdocs should start and end with content, excluding the very first and last line of the docblocks.
        'phpdoc_trim' => true,
];

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'tests/Commands/__snapshots__',
    ])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
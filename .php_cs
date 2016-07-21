<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('vendor')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(array(
        // Concatenation should be used with at least one whitespace around.
        'concat_with_spaces',
        // Unused use statements must be removed.
        'ordered_use',
        // Removes extra empty lines.
        'extra_empty_lines',
        // Removes line breaks between use statements.
        'remove_lines_between_uses',
        // An empty line feed should precede a return statement.
        'return',
        // Unused use statements must be removed.
        'unused_use',
        // Remove trailing whitespace at the end of blank lines.
        'whitespacy_lines',
        // There MUST be one blank line after the namespace declaration.
        'line_after_namespace',
        // There should be exactly one blank line before a namespace declaration.
        'single_blank_line_before_namespace',
        // Each namespace use MUST go on its own line and there MUST be one blank line after the use statements block.
        'single_line_after_imports',
        // Ensure there is no code on the same line as the PHP open tag and it is followed by a blankline.
        'blankline_after_open_tag',
        // Remove duplicated semicolons.
        'duplicate_semicolon',
        // PHP multi-line arrays should have a trailing comma.
        'multiline_array_trailing_comma',
        // There should be no empty lines after class opening brace.
        'no_blank_lines_after_class_opening',
        // There should not be blank lines between docblock and the documented element.
        'no_empty_lines_after_phpdocs',
        // Phpdocs should start and end with content, excluding the very first and last line of the docblocks.
        'phpdoc_trim',
        // Removes line breaks between use statements.
        'remove_lines_between_uses',
    ))
    ->finder($finder);

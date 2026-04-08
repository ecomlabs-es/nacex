<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('tests')
    ->exclude('upgrade')
    ->exclude('views')
    ->exclude('translations')
    ->exclude('node_modules')
    ->exclude('js')
    ->exclude('css')
    ->exclude('images')
    ->exclude('files')
    ->exclude('mails')
    ->exclude('lib')
    ->exclude('log')
    ->name('*.php')
;

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,

        // Ajustes pragmáticos para módulos PrestaShop legacy
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,

        // Desactivamos reglas que chocan con el estilo legacy del módulo
        'class_definition' => false,
        'braces_position' => false,
        'single_line_empty_body' => false,
        'visibility_required' => false,
    ])
    ->setFinder($finder)
;

<?php

$finder = new PhpCsFixer\Finder()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
;

return new PhpCsFixer\Config()
    ->setRules([
        '@Symfony' => true,
        '@PHP8x4Migration' => true,
        // Style existant du projet : espaces autour de l'opérateur de concaténation
        'concat_space' => ['spacing' => 'one'],
        // Style existant du projet : classes globales importées via `use` plutôt que préfixées par `\`
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false],
        // Style existant du projet : comparaisons non-yoda (`$value === null`)
        // Style existant du projet : attributs de paramètres sur la même ligne (`#[LiveArg] int $index`)
        'method_argument_space' => ['after_heredoc' => true, 'on_multiline' => 'ignore', 'attribute_placement' => 'same_line'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    ])
    ->setFinder($finder)
;

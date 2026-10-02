<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\MethodCall\RemoveNullNamedArgOnNullDefaultParamRector;
use Rector\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRector;
use Rector\Symfony\Symfony73\Rector\Class_\GetFiltersAndFunctionsToAsTwigAttributeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
    // Cible PHP lue depuis composer.json (require.php >= 8.4) : syntaxe moderne et dépréciations
    ->withPhpSets()
    // Sets Symfony, Doctrine, PHPUnit et Twig adaptés aux versions installées (composer.lock)
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    // Annotations et docblocks remplacés par des attributs PHP
    ->withAttributesSets()
    // Pas de codeQuality : il remplace les `=== null` par des `instanceof` (contraire au style du projet)
    ->withPreparedSets(deadCode: true, typeDeclarations: true)
    // Imports gérés par Rector pour les classes avec namespace, les classes globales par PHP-CS-Fixer
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSkip([
        // Réécrit les extensions Twig en attributs #[AsTwigFunction] : AbstractExtension n'est pas déprécié
        GetFiltersAndFunctionsToAsTwigAttributeRector::class,
        // Les tests passent parfois `null` explicitement pour rendre le cas testé lisible
        RemoveNullNamedArgOnNullDefaultParamRector::class,
        // Remplace les tests de nullité par des `instanceof` (contraire au style du projet)
        BinaryOpNullableToInstanceofRector::class,
    ])
;

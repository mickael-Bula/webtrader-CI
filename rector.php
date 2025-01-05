<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    // Remplace les annotations par des attributs PHP
    ->withAttributesSets(
        symfony: true,
        doctrine: true,
        sensiolabs: true,
    )
    // Règles supplémentaires pour améliorer la qualité du code
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        earlyReturn: true,
        symfonyCodeQuality: true,
    )
    // Les méthodes suivantes ne sont à utiliser qu'une fois par montée de version
    // ->withSets([LevelSetList::UP_TO_PHP_81,])
    // ->withSets([SymfonySetList::SYMFONY_64,])
;

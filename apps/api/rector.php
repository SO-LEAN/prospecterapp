<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
    )
    ->withComposerBased(doctrine: true, phpunit: true, symfony: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);

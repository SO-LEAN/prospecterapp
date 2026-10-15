<?php

use Rector\Config\RectorConfig;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForCommonObjectDenominatorRector;

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
    ->withComposerBased(phpunit: true, symfony: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    // type docblocks only where the signature cannot say it (project convention)
    ->withSkip([AddReturnDocblockForCommonObjectDenominatorRector::class]);

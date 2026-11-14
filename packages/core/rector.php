<?php

declare(strict_types=1);

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
    ->withComposerBased(phpunit: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSkip([AddReturnDocblockForCommonObjectDenominatorRector::class])
    // as many workers as the php container has processors (2, docker-compose.agents.yml), not as the host shows it
    ->withParallel(maxNumberOfProcess: 2);

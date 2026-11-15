<?php

use Rector\Config\RectorConfig;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockForCommonObjectDenominatorRector;

$config = RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    // its cache in the checkout, kept from one run to the next (the /tmp of the container goes with it)
    ->withCache(__DIR__.'/var/cache/rector')
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

// some files (make lint FILTER=...) in one process, a worker taking as long to start as to check them (make/php.mk);
// the whole project in as many workers as the php container has processors (2, docker-compose.agents.yml), not as the
// host shows it
return getenv('RECTOR_IN_ONE_PROCESS') ? $config->withoutParallel() : $config->withParallel(maxNumberOfProcess: 2);

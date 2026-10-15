<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['vendor'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PHP8x5Migration' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => false, 'remove_inheritdoc' => true, 'allow_unused_params' => false],
        'no_empty_phpdoc' => true,
        'phpdoc_trim' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_to_comment' => false,
        'declare_strict_types' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;

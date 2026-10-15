<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Contract of a context (ADR 0047) is plain data depending on nothing, not even on its own context: it can
 * leave the core as a package of its own when the context is deployed apart.
 */
class ContractTest extends TestCase
{
    #[DataProvider('contractFiles')]
    public function testAContractImportsNothing(string $file): void
    {
        preg_match_all('/^use ([\w\\\\]+)(?: as \w+)?;/m', (string) file_get_contents($file), $imports);

        $this->assertSame([], $imports[1]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function contractFiles(): iterable
    {
        $root = dirname(__DIR__, 2).'/src/';

        foreach (glob($root.'*/Contract', GLOB_ONLYDIR) ?: [] as $contract) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($contract, \FilesystemIterator::SKIP_DOTS)) as $file) {
                yield substr($file->getPathname(), strlen($root)) => [$file->getPathname()];
            }
        }
    }
}

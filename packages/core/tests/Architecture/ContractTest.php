<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A context's Contract (ADR 0047) is plain data that depends on nothing, not even on its own context. So it can
 * leave the core as its own package when the context is deployed separately.
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
            foreach (FilesUnder::the($contract) as $path) {
                yield substr($path, strlen($root)) => [$path];
            }
        }
    }
}

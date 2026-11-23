<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The core is pure PHP (docs/architecture/core.md). Besides PHP itself, it uses only the vendors listed here. A
 * development tool or a new package in src fails this test.
 */
class PermittedVendorsTest extends TestCase
{
    private const string CORE = 'Prospecter\\Core\\';

    /**
     * It tells what a phone number is (ADR 0072).
     */
    private const array PERMITTED_VENDORS = ['libphonenumber\\'];

    #[DataProvider('sourceFiles')]
    public function testTheCoreImportsOnlyPhpAndThePermittedVendors(string $file): void
    {
        preg_match_all('/^use (?:function |const )?([\w\\\\]+)(?: as \w+)?;/m', (string) file_get_contents($file), $imports);

        $this->assertSame([], array_values(array_filter($imports[1], $this->isForbidden(...))));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function sourceFiles(): iterable
    {
        $root = dirname(__DIR__, 2).'/src/';

        foreach (FilesUnder::the($root) as $path) {
            yield substr($path, strlen($root)) => [$path];
        }
    }

    private function isForbidden(string $import): bool
    {
        return !str_starts_with($import, self::CORE)
            && !array_any(self::PERMITTED_VENDORS, static fn (string $vendor): bool => str_starts_with($import, $vendor))
            && !$this->isPhp($import);
    }

    private function isPhp(string $import): bool
    {
        if (class_exists($import) || interface_exists($import) || enum_exists($import)) {
            return new \ReflectionClass($import)->isInternal();
        }

        return function_exists($import) && new \ReflectionFunction($import)->isInternal();
    }
}

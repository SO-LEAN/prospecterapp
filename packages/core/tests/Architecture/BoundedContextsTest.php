<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A bounded context speaks only to the shared kernel; the shared kernel speaks to nobody (ADR 0030). A
 * context reaches another one through a port of its own, implemented outside the core.
 */
class BoundedContextsTest extends TestCase
{
    private const array FORBIDDEN = [
        'Accounts' => ['Prospecting'],
        'Prospecting' => ['Accounts'],
        'SharedKernel' => ['Accounts', 'Prospecting'],
    ];

    #[DataProvider('contexts')]
    public function testAContextDoesNotUseAnotherOne(string $context): void
    {
        $this->assertSame([], $this->forbiddenUsesIn($context));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function contexts(): iterable
    {
        foreach (array_keys(self::FORBIDDEN) as $context) {
            yield $context => [$context];
        }
    }

    /**
     * @return list<string> "file: used class" for every forbidden use
     */
    private function forbiddenUsesIn(string $context): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $forbidden = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.$context, \FilesystemIterator::SKIP_DOTS)) as $file) {
            preg_match_all('/^use Prospecter\\\\Core\\\\(\w+)\\\\([\w\\\\]+);/m', (string) file_get_contents($file->getPathname()), $uses, PREG_SET_ORDER);

            foreach ($uses as [, $usedContext, $class]) {
                if (in_array($usedContext, self::FORBIDDEN[$context], true)) {
                    $forbidden[] = sprintf('%s: %s\\%s', substr($file->getPathname(), strlen($root)), $usedContext, $class);
                }
            }
        }

        return $forbidden;
    }
}

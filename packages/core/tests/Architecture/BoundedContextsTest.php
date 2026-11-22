<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A bounded context speaks only to the shared kernel; the shared kernel speaks to nobody (ADR 0030). A
 * context asks another one through a port of its own, implemented outside the core by an anticorruption
 * layer that sees only the Contract of the other context (ADR 0045, 0047), its doubles too; it reacts to the
 * integration events of that Contract, never to the domain events of the other context (ADR 0046).
 */
class BoundedContextsTest extends TestCase
{
    private const string CONTRACT = 'Contract\\';
    private const string CONTRACT_EVENTS = 'Contract\\Event\\';

    /**
     * An import of the core, aliased or not: its context, then the rest of its name.
     */
    private const string IMPORT = '/^use Prospecter\\\\Core\\\\(\w+)\\\\([\w\\\\]+)(?: as \w+)?;/m';

    private const string SHARED_KERNEL = 'SharedKernel';

    public function testADoubleOfAPortSeesOfTheOtherContextsOnlyWhatTheyPublish(): void
    {
        $this->assertSame([], $this->unpublishedUsesInTheDoubles());
    }

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
        foreach (self::all() as $context) {
            yield $context => [$context];
        }
    }

    /**
     * The folders of src: a new context is checked as soon as it is added.
     *
     * @return list<string>
     */
    private static function all(): array
    {
        return array_map(basename(...), glob(dirname(__DIR__, 2).'/src/*', GLOB_ONLYDIR) ?: []);
    }

    /**
     * @return list<string>
     */
    private static function forbiddenTo(string $context): array
    {
        return array_values(array_diff(self::all(), [$context, self::SHARED_KERNEL]));
    }

    /**
     * @return list<string> "file: used class" for every forbidden use
     */
    private function forbiddenUsesIn(string $context): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $forbidden = [];

        foreach (FilesUnder::the($root.$context) as $path) {
            preg_match_all(self::IMPORT, (string) file_get_contents($path), $uses, PREG_SET_ORDER);

            foreach ($uses as [, $usedContext, $class]) {
                if (in_array($usedContext, self::forbiddenTo($context), true) && !str_starts_with($class, self::CONTRACT_EVENTS)) {
                    $forbidden[] = sprintf('%s: %s\\%s', substr($path, strlen($root)), $usedContext, $class);
                }
            }
        }

        return $forbidden;
    }

    /**
     * @return list<string> "file: used class" for every class of another context than the one of the port
     */
    private function unpublishedUsesInTheDoubles(): array
    {
        $forbidden = [];

        foreach (glob(dirname(__DIR__).'/Double/*.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);
            $port = $this->contextOfThePortIn($source);

            if (null === $port) {
                continue;
            }

            preg_match_all(self::IMPORT, $source, $uses, PREG_SET_ORDER);

            foreach ($uses as [, $usedContext, $class]) {
                if (in_array($usedContext, self::forbiddenTo($port), true) && !str_starts_with($class, self::CONTRACT)) {
                    $forbidden[] = sprintf('%s: %s\\%s', basename($file), $usedContext, $class);
                }
            }
        }

        return $forbidden;
    }

    /**
     * The context of the port a double implements, null when it implements none of a context.
     */
    private function contextOfThePortIn(string $source): ?string
    {
        if (1 !== preg_match('/^final (?:readonly )?class \\w+ implements (\\w+)/m', $source, $implements)
            || 1 !== preg_match('/^use Prospecter\\\\Core\\\\(\\w+)\\\\[\\w\\\\]*\\\\'.$implements[1].';/m', $source, $port)) {
            return null;
        }

        return self::SHARED_KERNEL === $port[1] ? null : $port[1];
    }
}

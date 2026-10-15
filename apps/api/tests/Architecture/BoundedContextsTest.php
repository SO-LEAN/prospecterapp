<?php

namespace Tests\App\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * The API is grouped by bounded context like the core (ADR 0030): a context folder uses neither the other
 * context of the core nor its API folder; `Me` and `Dashboard` compose contexts. The exceptions cross the
 * border on purpose.
 */
#[Group('unit')]
class BoundedContextsTest extends TestCase
{
    private const array FORBIDDEN = [
        'Accounts' => ['Prospecter\Core\PersonalData\\', 'Prospecter\Core\Prospecting\\', 'App\PersonalData\\', 'App\Prospecting\\'],
        'PersonalData' => ['Prospecter\Core\Accounts\\', 'Prospecter\Core\Prospecting\\', 'App\Accounts\\', 'App\Prospecting\\'],
        'Prospecting' => ['Prospecter\Core\Accounts\\', 'Prospecter\Core\PersonalData\\', 'App\Accounts\\', 'App\PersonalData\\'],
        'Shared' => ['Prospecter\Core\Accounts\\', 'Prospecter\Core\PersonalData\\', 'Prospecter\Core\Prospecting\\', 'App\Accounts\\', 'App\PersonalData\\', 'App\Prospecting\\'],
    ];

    private const array CROSSING_ON_PURPOSE = [
        'Prospecting/AccountsProspectors.php', // anticorruption layer: Prospecting reads its prospectors from Accounts
        'PersonalData/AccountsReaders.php', // anticorruption layer: PersonalData reads its readers from Accounts
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
            $path = substr($file->getPathname(), strlen($root));
            preg_match_all('/^use ([\w\\\\]+)(?: as \w+)?;/m', (string) file_get_contents($file->getPathname()), $uses);

            foreach ($uses[1] as $class) {
                foreach (self::FORBIDDEN[$context] as $prefix) {
                    if (str_starts_with($class, $prefix) && !in_array($path, self::CROSSING_ON_PURPOSE, true)) {
                        $forbidden[] = sprintf('%s: %s', $path, $class);
                    }
                }
            }
        }

        return $forbidden;
    }
}

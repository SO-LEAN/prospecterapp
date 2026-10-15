<?php

namespace Tests\App\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each bounded context keeps its aggregates in its own database (ADR 0038): only its persistence adapters
 * reach it, through the entity manager named after it.
 */
class DatabasePerContextTest extends TestCase
{
    private const string ENTITY_MANAGER = '/EntityManagerInterface \$(\w+)/';

    private const array ENTITY_MANAGERS = [
        'Accounts' => 'accountsEntityManager',
        'PersonalData' => 'personalDataEntityManager',
        'Prospecting' => 'prospectingEntityManager',
    ];

    #[DataProvider('contexts')]
    public function testThePersistenceOfAContextUsesItsOwnDatabase(string $context, string $entityManager): void
    {
        foreach ($this->entityManagersIn($context.'/Persistence') as $path => $used) {
            $this->assertSame([$entityManager], array_values(array_unique($used)), $path);
        }
    }

    public function testNothingElseReachesADatabase(): void
    {
        $outsideThePersistence = array_filter(
            array_keys($this->entityManagersIn('')),
            static fn (string $path): bool => !str_contains($path, '/Persistence/'),
        );

        $this->assertSame([], array_values($outsideThePersistence));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function contexts(): iterable
    {
        foreach (self::ENTITY_MANAGERS as $context => $entityManager) {
            yield $context => [$context, $entityManager];
        }
    }

    /**
     * @return array<string, list<string>> the entity managers injected, by file using one
     */
    private function entityManagersIn(string $folder): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $found = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.$folder, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (preg_match_all(self::ENTITY_MANAGER, (string) file_get_contents($file->getPathname()), $matches)) {
                $found[substr($file->getPathname(), strlen($root))] = $matches[1];
            }
        }

        return $found;
    }
}

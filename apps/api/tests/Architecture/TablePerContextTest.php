<?php

namespace Tests\App\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each bounded context keeps its aggregates in its own table (ADR 0038, 0048): only its persistence adapters
 * reach it, through the table named after it; the shared persistence (the tables, the journal) serves them all.
 */
class TablePerContextTest extends TestCase
{
    private const string TABLE = '/Table \$(\w+)/';

    private const array TABLES = [
        'Accounts' => 'accountsTable',
        'PersonalData' => 'personalDataTable',
        'Prospecting' => 'prospectingTable',
    ];

    #[DataProvider('contexts')]
    public function testThePersistenceOfAContextUsesItsOwnTable(string $context, string $table): void
    {
        foreach ($this->tablesIn($context.'/Persistence') as $path => $used) {
            $this->assertSame([$table], array_values(array_unique($used)), $path);
        }
    }

    public function testNothingElseReachesATable(): void
    {
        $outsideThePersistence = array_filter(
            array_keys($this->tablesIn('')),
            static fn (string $path): bool => !str_contains($path, '/Persistence/'),
        );

        $this->assertSame([], array_values($outsideThePersistence));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function contexts(): iterable
    {
        foreach (self::TABLES as $context => $table) {
            yield $context => [$context, $table];
        }
    }

    /**
     * @return array<string, list<string>> the tables injected, by file using one
     */
    private function tablesIn(string $folder): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $found = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.$folder, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (preg_match_all(self::TABLE, (string) file_get_contents($file->getPathname()), $matches)) {
                $found[substr($file->getPathname(), strlen($root))] = $matches[1];
            }
        }

        return $found;
    }
}

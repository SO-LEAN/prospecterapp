<?php

namespace Tests\App\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each bounded context keeps its aggregates in its own table (ADR 0038, 0048): only its persistence adapters
 * reach it, through the table named after it; the shared persistence (the tables) serves them all. The check of the
 * consistency alone reads the tables of every context, and only reads them (ADR 0105).
 */
class TablePerContextTest extends TestCase
{
    private const string TABLE = '/Table \$(\w+)/';

    private const array TABLES = [
        'Accounts' => 'accountsTable',
        'PersonalData' => 'personalDataTable',
        'Prospecting' => 'prospectingTable',
        'Reporting' => 'reportingTable',
    ];

    /** the only code reading the tables of several contexts */
    private const string READING_EVERY_TABLE = 'Consistency/ContextTables.php';
    private const array WRITES = ['->putting(', '->replacing(', '->adding(', '->deleting(', '->transact(', '->batch('];

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
            static fn (string $path): bool => !str_contains($path, '/Persistence/') && self::READING_EVERY_TABLE !== $path,
        );

        $this->assertSame([], array_values($outsideThePersistence));
    }

    public function testTheCheckOfTheConsistencyReadsEveryTableAndWritesNone(): void
    {
        $this->assertSame(array_values(self::TABLES), $this->tablesIn('Consistency')[self::READING_EVERY_TABLE]);
        $code = (string) file_get_contents(dirname(__DIR__, 2).'/src/'.self::READING_EVERY_TABLE);

        foreach (self::WRITES as $write) {
            $this->assertStringNotContainsString($write, $code);
        }
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

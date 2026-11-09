<?php

namespace Tests\App\Shared;

use App\Shared\Persistence\Table;
use Aws\DynamoDb\DynamoDbClient;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * The items of the former activity journal are deleted from the table of every context, and nothing else.
 */
final class PurgeJournalCommandTest extends ApiTestCase
{
    private const array TABLES = ['app.table.accounts', 'app.table.personal_data', 'app.table.prospecting'];
    private const int ENTRIES_BY_TABLE = 30;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            $this->writeJournalEntriesIn($this->table($table));
        }
    }

    public function testThePurgeDeletesTheItemsOfTheJournalOnly(): void
    {
        $othersBefore = $this->itemsOtherThanTheJournal();

        $this->assertSame(Command::SUCCESS, $this->purge()->getStatusCode());

        $this->assertSame([], $this->journalItems());
        $this->assertEquals($othersBefore, $this->itemsOtherThanTheJournal());
    }

    public function testThePurgeRunAgainFindsNothing(): void
    {
        $this->purge();

        $this->assertStringContainsString('0 items of the journal deleted', $this->purge()->getDisplay());
    }

    public function testADryRunCountsWithoutDeleting(): void
    {
        $journalBefore = $this->journalItems();

        $display = $this->purge(['--dry-run' => true])->getDisplay();

        $this->assertStringContainsString((3 * self::ENTRIES_BY_TABLE).' items of the journal to delete', $display);
        $this->assertEquals($journalBefore, $this->journalItems());
    }

    /**
     * @param array<string, bool> $options
     */
    private function purge(array $options = []): CommandTester
    {
        $command = new CommandTester(new Application(self::$kernel)->find('app:journal:purge'));
        $command->execute($options);

        return $command;
    }

    private function writeJournalEntriesIn(Table $table): void
    {
        $table->batch(array_map(static fn (int $entry): array => [
            'PK' => 'JOURNAL#019a0000-0000-7000-8000-00000000000'.($entry % 3),
            'SK' => sprintf('2026-11-09T10:00:%02d+00:00#%d', $entry, $entry),
            'event' => 'ExchangeNoted',
        ], range(1, self::ENTRIES_BY_TABLE)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function journalItems(): array
    {
        return array_merge(...array_map(fn (string $table): array => $this->table($table)->scan('JOURNAL#'), self::TABLES));
    }

    /**
     * @return array<string, list<array<string, mixed>>> the items of each table, as DynamoDB keeps them
     */
    private function itemsOtherThanTheJournal(): array
    {
        $items = [];

        foreach (self::TABLES as $table) {
            $items[$table] = array_values(array_filter($this->everyItemOf($this->table($table)), static fn (array $item): bool => !str_starts_with($item['PK']['S'], 'JOURNAL#')));
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function everyItemOf(Table $table): array
    {
        $pages = self::getContainer()->get(DynamoDbClient::class)->getPaginator('Scan', ['TableName' => $table->name, 'ConsistentRead' => true]);
        $items = [];

        foreach ($pages as $page) {
            $items = [...$items, ...$page['Items']];
        }

        usort($items, static fn (array $one, array $other): int => [$one['PK']['S'], $one['SK']['S']] <=> [$other['PK']['S'], $other['SK']['S']]);

        return $items;
    }

    private function table(string $id): Table
    {
        return self::getContainer()->get($id);
    }
}

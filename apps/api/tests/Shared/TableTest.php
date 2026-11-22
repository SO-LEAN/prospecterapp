<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Tests\App\Base\ApiTestCase;

class TableTest extends ApiTestCase
{
    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = self::tableOf('accounts');
    }

    public function testTheItemsAreReadInTheOrderOfTheirPartitions(): void
    {
        $this->storedNames('Ada', 'Grace');

        $found = $this->table->itemsInTheOrderOf(['TEST#grace', 'TEST#ada'], 'TEST');

        $this->assertSame(['Grace', 'Ada'], self::namesOf($found));
    }

    public function testAPartitionWithoutAnItemIsLeftOut(): void
    {
        $this->storedNames('Ada');

        $found = $this->table->itemsInTheOrderOf(['TEST#alan', 'TEST#ada'], 'TEST');

        $this->assertSame(['Ada'], self::namesOf($found));
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<mixed>
     */
    private static function namesOf(array $items): array
    {
        return array_map(static fn (array $item): mixed => $item['name'] ?? null, $items);
    }

    private function storedNames(string ...$names): void
    {
        $this->table->transact(array_map(fn (string $name): Write => $this->table->putting(['PK' => 'TEST#'.strtolower($name), 'SK' => 'TEST', 'name' => $name]), array_values($names)));
    }
}

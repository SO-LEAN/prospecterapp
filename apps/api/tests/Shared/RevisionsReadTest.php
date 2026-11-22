<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Persistence\Table;
use Symfony\Contracts\Service\ResetInterface;
use Tests\App\Base\ApiTestCase;

/**
 * The revision of an item as a reader read it, to expose it with the resource read from it (ADR 0187).
 */
class RevisionsReadTest extends ApiTestCase
{
    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        $table = self::getContainer()->get('app.table.accounts');
        $this->assertInstanceOf(Table::class, $table);
        $this->table = $table;
    }

    public function testTheRevisionOfAnItemWatchedIsTheOneItsReadFound(): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'TEST#ada', 'SK' => 'TEST', 'revision' => 3])]);

        $this->table->watchRevision('TEST#ada', 'TEST');
        $this->table->item('TEST#ada', 'TEST');

        $this->assertSame(3, $this->table->revisionRead('TEST#ada', 'TEST'));
    }

    public function testTheRevisionOfTheLastReadOfAnItemWatchedIsKept(): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'TEST#ada', 'SK' => 'TEST', 'revision' => 3])]);
        $this->table->watchRevision('TEST#ada', 'TEST');
        $this->table->item('TEST#ada', 'TEST');
        $this->table->transact([$this->table->putting(['PK' => 'TEST#ada', 'SK' => 'TEST', 'revision' => 4])]);

        $this->table->items([['TEST#ada', 'TEST']]);

        $this->assertSame(4, $this->table->revisionRead('TEST#ada', 'TEST'));
    }

    public function testAnItemNoReaderWatchesIsNotKept(): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'TEST#ada', 'SK' => 'TEST', 'revision' => 3])]);
        $this->table->item('TEST#ada', 'TEST');

        $this->expectException(\LogicException::class);

        $this->table->revisionRead('TEST#ada', 'TEST');
    }

    public function testTheRevisionsReadAreForgottenAtTheEndOfTheRequest(): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'TEST#ada', 'SK' => 'TEST', 'revision' => 3])]);
        $this->table->watchRevision('TEST#ada', 'TEST');
        $this->table->item('TEST#ada', 'TEST');

        $resetter = self::getContainer()->get('services_resetter');
        $this->assertInstanceOf(ResetInterface::class, $resetter);
        $resetter->reset();

        $this->expectException(\LogicException::class);
        $this->table->revisionRead('TEST#ada', 'TEST');
    }
}

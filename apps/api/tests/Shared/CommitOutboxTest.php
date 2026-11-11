<?php

namespace Tests\App\Shared;

use App\Shared\Messaging\OutboxDelivery;
use App\Shared\Messaging\TransportedEvent;
use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Table;
use Tests\App\Base\ApiTestCase;

/**
 * The events delivered through the outbox are written at the outermost commit, in one item of the table of the
 * context they happened in, with what the unit writes; none when nothing is published. A transaction spans one use
 * case, hence one context: a commit publishing in two is refused.
 */
class CommitOutboxTest extends ApiTestCase
{
    private DynamoUnitOfWork $unitOfWork;
    private OutboxDelivery $delivery;
    private Table $accounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $this->delivery = self::getContainer()->get(OutboxDelivery::class);
        $table = self::getContainer()->get('app.table.accounts');
        $this->assertInstanceOf(Table::class, $table);
        $this->accounts = $table;
    }

    public function testAUnitPublishingNothingWritesNoOutbox(): void
    {
        $this->unitOfWork->begin();
        $this->accounts->transact([$this->accounts->putting(['PK' => 'TEST#ada', 'SK' => 'TEST'])]);
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame([], $this->accounts->scan('OUTBOX#'));
    }

    public function testTheEventsOfAUnitAreWrittenInOneItemAtTheOutermostCommit(): void
    {
        $this->unitOfWork->begin();
        $this->unitOfWork->begin();
        $this->delivery->deliver($this->event('prospecter.accounts.internal', 'AccountOpened.v1'));
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1'));
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame([], $this->accounts->scan('OUTBOX#'), 'the inner commit writes nothing');

        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame([['AccountOpened.v1', 'AccountOpened.v1']], $this->detailTypesOfTheOutbox());
    }

    public function testAnEventPublishedTwiceByACommitIsWrittenOnce(): void
    {
        $this->unitOfWork->begin();
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1'));
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1'));
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1', 'Globex'));
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame([['AccountOpened.v1', 'AccountOpened.v1']], $this->detailTypesOfTheOutbox());
    }

    public function testACommitPublishingInTwoContextsIsRefusedBeforeAnythingIsSent(): void
    {
        $this->unitOfWork->begin();
        $this->accounts->transact([$this->accounts->putting(['PK' => 'TEST#ada', 'SK' => 'TEST'])]);
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1'));
        $this->delivery->deliver($this->event('prospecter.personal-data', 'ContactCollected.v1'));

        try {
            $this->unitOfWork->commit();
            $this->fail('A commit spanning two contexts was expected to be refused');
        } catch (\LogicException) {
            $this->unitOfWork->discard();
        }

        $this->assertNull($this->accounts->item('TEST#ada', 'TEST'));
        $this->assertSame([], $this->accounts->scan('OUTBOX#'));
        $this->assertSame([], self::getContainer()->get('app.table.personal_data')->scan('OUTBOX#'));
    }

    public function testAnEventPublishedOutsideAUnitIsACommitOfItsOwn(): void
    {
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1'));

        $this->assertCount(1, $this->accounts->scan('OUTBOX#'));
    }

    public function testADiscardedUnitThrowsItsEventsAway(): void
    {
        $this->unitOfWork->begin();
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1'));
        $this->unitOfWork->discard();

        $this->unitOfWork->begin();
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame([], $this->accounts->scan('OUTBOX#'));
    }

    public function testAUnitRefusedAtItsCommitWritesNoOutbox(): void
    {
        $this->accounts->transact([$this->accounts->putting(['PK' => 'TEST#ada', 'SK' => 'TEST'])]);
        $this->unitOfWork->begin();
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1'));
        $this->accounts->transact([$this->accounts->putting(['PK' => 'TEST#ada', 'SK' => 'TEST'], 'attribute_not_exists(PK)')]);

        try {
            $this->unitOfWork->commit();
            $this->fail('The unit was expected to be refused at its commit');
        } catch (ConditionFailed) {
            $this->unitOfWork->discard();
        }

        $this->assertSame([], $this->accounts->scan('OUTBOX#'));
    }

    public function testAnOutboxTooBigForAnItemIsRefusedBeforeAnythingIsSent(): void
    {
        $this->unitOfWork->begin();
        $this->accounts->transact([$this->accounts->putting(['PK' => 'TEST#ada', 'SK' => 'TEST'])]);
        $this->delivery->deliver($this->event('prospecter.accounts', 'AccountOpened.v1', str_repeat('a', 400 * 1024)));

        try {
            $this->unitOfWork->commit();
            $this->fail('An outbox over the size of an item was expected to be refused');
        } catch (\LogicException) {
            $this->unitOfWork->discard();
        }

        $this->assertNull($this->accounts->item('TEST#ada', 'TEST'));
    }

    /**
     * @return list<list<string>> the detail-types of the events of each item of outbox of the table accounts
     */
    private function detailTypesOfTheOutbox(): array
    {
        return array_map(static fn (array $item): array => array_column(json_decode((string) $item['events'], true, flags: \JSON_THROW_ON_ERROR), 'detailType'), $this->accounts->scan('OUTBOX#'));
    }

    private function event(string $source, string $detailType, string $name = 'SOLEAN IT'): TransportedEvent
    {
        return new TransportedEvent($source, $detailType, self::newIdentity(), '2026-11-10T09:00:00.000000Z', self::newIdentity(), null, 0, ['name' => $name]);
    }
}

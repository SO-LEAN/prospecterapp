<?php

namespace Tests\App\Shared;

use App\PersonalData\Persistence\DynamoPersonRepository;
use App\Shared\Identities;
use App\Shared\Messaging\CommitOutbox;
use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Table;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Component\Clock\NativeClock;
use Tests\App\Base\ApiTestCase;

/**
 * What a use case writes in the tables is sent in one TransactWriteItems at the outermost commit, all or nothing.
 */
class DynamoUnitOfWorkTest extends ApiTestCase
{
    private DynamoUnitOfWork $unitOfWork;
    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $table = self::getContainer()->get('app.table.accounts');
        $this->assertInstanceOf(Table::class, $table);
        $this->table = $table;
    }

    public function testWithoutAUnitAWriteIsSentAtOnce(): void
    {
        $this->put('ada', 'Ada');

        $this->assertSame('Ada', $this->storedNameOf('ada'));
    }

    public function testANestedUnitWritesOnlyAtTheOutermostCommit(): void
    {
        $this->unitOfWork->begin();
        $this->unitOfWork->begin();
        $this->put('ada', 'Ada');
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertNull($this->storedNameOf('ada'), 'the inner commit writes nothing');

        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame('Ada', $this->storedNameOf('ada'));
    }

    public function testADiscardWithoutItsCommitThrowsTheWholeUnitAway(): void
    {
        $this->unitOfWork->begin();
        $this->put('ada', 'Ada');
        $this->unitOfWork->begin();
        $this->put('grace', 'Grace');
        $this->unitOfWork->discard();
        $this->unitOfWork->discard();

        $this->put('alan', 'Alan');

        $this->assertNull($this->storedNameOf('ada'));
        $this->assertNull($this->storedNameOf('grace'));
        $this->assertSame('Alan', $this->storedNameOf('alan'), 'once thrown away, no unit is open');
    }

    public function testAUnitDiscardedAtOneLevelWritesNothingOfWhatFollows(): void
    {
        $this->unitOfWork->begin();
        $this->unitOfWork->begin();
        $this->unitOfWork->discard();
        $this->put('ada', 'Ada');

        try {
            $this->unitOfWork->commit();
            $this->fail('A discarded unit was expected to be refused');
        } catch (\LogicException) {
            $this->unitOfWork->discard();
        }

        $this->assertNull($this->storedNameOf('ada'), 'the event bus still calls the other reactions');
    }

    public function testTwoWritesOfTheSameItemMakeOneTheLastWinning(): void
    {
        $this->unitOfWork->begin();
        $this->put('ada', 'Ada');
        $this->put('ada', 'Ada Lovelace');
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame('Ada Lovelace', $this->storedNameOf('ada'));
    }

    public function testTwoAdditionsToAnItemInAUnitBothCount(): void
    {
        $this->add('figures', ['organizations' => 1]);
        $this->unitOfWork->begin();
        $this->add('figures', ['organizations' => 2, 'contacts' => 1]);
        $this->add('figures', ['organizations' => -1]);
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertEquals(['organizations' => 2, 'contacts' => 1], $this->storedCountersOf('figures'));
    }

    public function testAnAdditionAboutToBeWrittenLeavesTheItemReadAsStored(): void
    {
        $this->add('figures', ['organizations' => 1]);
        $this->unitOfWork->begin();
        $this->add('figures', ['organizations' => 2]);

        $this->assertSame(1, $this->table->item('TEST#figures', 'TEST')['organizations'] ?? null);
        $this->assertSame([1], array_column($this->table->query('TEST#figures'), 'organizations'));

        $this->unitOfWork->discard();
    }

    public function testAPutReplacingAnotherAboutToBeWrittenKeepsTheConditionOnTheStoredItem(): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'TEST#fact', 'SK' => 'TEST', 'revision' => 1])]);
        $this->unitOfWork->begin();
        $this->table->transact([$this->table->replacing(['PK' => 'TEST#fact', 'SK' => 'TEST', 'revision' => 2], 'revision = :revision', [':revision' => 1])]);
        $this->table->transact([$this->table->replacing(['PK' => 'TEST#fact', 'SK' => 'TEST', 'revision' => 3], 'revision = :revision', [':revision' => 2])]);
        $this->unitOfWork->commit();
        $this->unitOfWork->discard();

        $this->assertSame(3, $this->asAnotherRequest($this->table)->item('TEST#fact', 'TEST')['revision'] ?? null);
    }

    public function testAPutWhoseStoredItemChangedMeanwhileIsCancelled(): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'TEST#fact', 'SK' => 'TEST', 'revision' => 1])]);
        $this->unitOfWork->begin();
        $this->table->transact([$this->table->replacing(['PK' => 'TEST#fact', 'SK' => 'TEST', 'revision' => 2], 'revision = :revision', [':revision' => 1])]);
        $this->asAnotherRequest($this->table)->transact([$this->table->putting(['PK' => 'TEST#fact', 'SK' => 'TEST', 'revision' => 5])]);

        try {
            $this->unitOfWork->commit();
            $this->fail('The put was expected to be cancelled');
        } catch (ConditionFailed) {
            $this->unitOfWork->discard();
        }

        $this->assertSame(5, $this->asAnotherRequest($this->table)->item('TEST#fact', 'TEST')['revision'] ?? null);
    }

    public function testMoreThanAHundredWritesAreRefusedBeforeAnyIsSent(): void
    {
        $this->unitOfWork->begin();

        foreach (range(0, 100) as $number) {
            $this->put('member-'.$number, 'Member '.$number);
        }

        try {
            $this->unitOfWork->commit();
            $this->fail('The unit was expected to be refused');
        } catch (\LogicException) {
            $this->unitOfWork->discard();
        }

        $this->assertNull($this->storedNameOf('member-0'));
    }

    public function testAReadByKeySeesWhatTheUnitIsAboutToWrite(): void
    {
        $this->put('grace', 'Grace');
        $this->unitOfWork->begin();
        $this->put('ada', 'Ada');
        $this->table->transact([$this->table->deleting('TEST#grace', 'TEST')]);

        $this->assertSame('Ada', $this->readNameOf('ada'));
        $this->assertNull($this->readNameOf('grace'), 'a delete about to be written reads as an absence');
        $this->assertSame(['Ada'], array_column($this->table->items([['TEST#ada', 'TEST'], ['TEST#grace', 'TEST']]), 'name'));

        $this->unitOfWork->discard();
        $this->assertSame('Grace', $this->storedNameOf('grace'));
    }

    public function testTheQueryOfAPartitionSeesWhatTheUnitIsAboutToWrite(): void
    {
        $this->link('ada', 'GROUP#analysts');
        $this->link('grace', 'GROUP#analysts');
        $this->unitOfWork->begin();
        $this->table->transact([$this->table->deleting('LINK#grace', 'GROUP#analysts')]);
        $this->link('alan', 'GROUP#analysts');

        $this->assertSame(['alan', 'ada'], array_column($this->table->query('GROUP#analysts', Table::BY_GSI1), 'name'));
        $this->assertSame(['ada'], array_column($this->table->query('GROUP#analysts', Table::BY_GSI1, before: 'alan'), 'name'));
        $this->assertSame(['alan'], array_column($this->table->query('GROUP#analysts', Table::BY_GSI1, limit: 1), 'name'));

        $this->unitOfWork->discard();
    }

    public function testARefusalDetectedAtTheCommitIsTheOneItsWriteCarriesNothingWritten(): void
    {
        $persons = self::getContainer()->get(DynamoPersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $ada = $this->personWithTheEmail($account, 'ada@solean-it.io');
        $this->unitOfWork->begin();
        $persons->save($ada);
        $this->anotherRequestSaves($this->personWithTheEmail($account, 'ada@solean-it.io'));

        try {
            $this->unitOfWork->commit();
            $this->fail('The email was expected to be refused');
        } catch (EmailAlreadyUsed $refusal) {
            $this->assertInstanceOf(ConditionFailed::class, $refusal->getPrevious(), 'the cancellation is its cause');
            $this->unitOfWork->discard();
        }

        $this->assertSame([], $persons->ofIds([$ada->id()]));
    }

    private function put(string $id, string $name): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'TEST#'.$id, 'SK' => 'TEST', 'name' => $name])]);
    }

    /**
     * @param array<string, int> $increments
     */
    private function add(string $id, array $increments): void
    {
        $this->table->transact([$this->table->adding('TEST#'.$id, 'TEST', $increments)]);
    }

    /**
     * @return array<string, mixed> the counters of the item as another request reads them
     */
    private function storedCountersOf(string $id): array
    {
        return array_diff_key($this->asAnotherRequest($this->table)->item('TEST#'.$id, 'TEST') ?? [], ['PK' => true, 'SK' => true]);
    }

    private function link(string $id, string $group): void
    {
        $this->table->transact([$this->table->putting(['PK' => 'LINK#'.$id, 'SK' => $group, 'GSI1PK' => $group, 'GSI1SK' => $id, 'name' => $id])]);
    }

    private function readNameOf(string $id): ?string
    {
        return $this->table->item('TEST#'.$id, 'TEST')['name'] ?? null;
    }

    /**
     * The name as another request reads it, in the table.
     */
    private function storedNameOf(string $id): ?string
    {
        return $this->asAnotherRequest($this->table)->item('TEST#'.$id, 'TEST')['name'] ?? null;
    }

    /**
     * The same email taken meanwhile by another request.
     */
    private function anotherRequestSaves(Person $person): void
    {
        $table = self::getContainer()->get('app.table.personal_data');
        $this->assertInstanceOf(Table::class, $table);
        new DynamoPersonRepository($this->asAnotherRequest($table))->save($person);
    }

    /**
     * The same table, with the unit of work of another request.
     */
    private function asAnotherRequest(Table $table): Table
    {
        $client = self::getContainer()->get(DynamoDbClient::class);

        return new Table($client, self::getContainer()->get(Marshaler::class), new DynamoUnitOfWork($client, new CommitOutbox([], self::getContainer()->get(Marshaler::class), new Identities(), new NativeClock())), $table->name);
    }

    private function personWithTheEmail(AccountId $account, string $email): Person
    {
        return Person::collect(new PersonId(self::newIdentity()), $account, Purpose::Prospecting, new \DateTimeImmutable(), contactDetails: new ContactDetails(new Email($email)));
    }
}

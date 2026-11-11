<?php

namespace Tests\App\PersonalData;

use App\Shared\Persistence\DynamoUnitOfWork;
use Prospecter\Core\PersonalData\Application\Port\ContactFactsRetriever;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The facts of the contacts, read from the items `DynamoPersonRepository` writes: the revision it increments at
 * each save, what the open unit of work is about to write, nothing for the profile of a member, the contacts of an
 * account by slice.
 */
class DynamoContactFactsRetrieverTest extends ApiTestCase
{
    private AccountId $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
    }

    public function testEachSaveIncrementsTheRevision(): void
    {
        $contact = $this->kept(Purpose::Prospecting, 'Lovelace');
        $this->persons()->save($contact);

        $this->persons()->save($contact);

        $this->assertEquals(new ContactFactsResponse($contact->id()->value, 2, true), $this->retriever()->of($contact->id()));
    }

    public function testTheFactsAreReadAsTheUnitOfWorkIsAboutToWriteThem(): void
    {
        $contact = $this->kept(Purpose::Prospecting, 'Lovelace');
        $unitOfWork = self::getContainer()->get(DynamoUnitOfWork::class);
        $unitOfWork->begin();

        $this->persons()->save($contact);
        $facts = $this->retriever()->of($contact->id());
        $unitOfWork->discard();

        $this->assertEquals(new ContactFactsResponse($contact->id()->value, 1, true), $facts);
    }

    public function testAForgottenContactIsAbsent(): void
    {
        $contact = $this->kept(Purpose::Prospecting, 'Lovelace');
        $this->persons()->save($contact);

        $this->persons()->remove($contact);

        $this->assertEquals(ContactFactsResponse::absent($contact->id()->value), $this->retriever()->of($contact->id()));
    }

    public function testTheProfileOfAMemberHasNoFactsOfContact(): void
    {
        $profile = $this->kept(Purpose::Membership, 'Hopper');
        $this->persons()->save($profile);

        $this->assertNull($this->retriever()->of($profile->id()));
        $this->assertSame([], $this->retriever()->sliceOf($this->account, null, 10)->content);
    }

    public function testTheFactsOfAnAccountComeBySliceNewestFirst(): void
    {
        $contacts = array_map(fn (string $name): Person => $this->kept(Purpose::Prospecting, $name), ['Lovelace', 'Turing', 'Torvalds']);
        array_walk($contacts, $this->persons()->save(...));

        $first = $this->retriever()->sliceOf($this->account, null, 2);
        $next = $this->retriever()->sliceOf($this->account, $first->next, 2);

        $this->assertSame([$contacts[2]->id()->value, $contacts[1]->id()->value], self::idsOf($first->content));
        $this->assertSame([$contacts[0]->id()->value], self::idsOf($next->content));
        $this->assertNull($next->next);
    }

    /**
     * @param list<ContactFactsResponse> $facts
     *
     * @return list<string>
     */
    private static function idsOf(array $facts): array
    {
        return array_map(static fn (ContactFactsResponse $contact): string => $contact->personId, $facts);
    }

    private function kept(Purpose $purpose, string $lastName): Person
    {
        return Person::collect(new PersonId(self::newIdentity()), $this->account, $purpose, new \DateTimeImmutable(), new PersonName(lastName: $lastName));
    }

    private function persons(): PersonRepository
    {
        return self::getContainer()->get(PersonRepository::class);
    }

    private function retriever(): ContactFactsRetriever
    {
        return self::getContainer()->get(ContactFactsRetriever::class);
    }
}

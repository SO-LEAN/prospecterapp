<?php

namespace Tests\App\PersonalData;

use App\PersonalData\Persistence\DynamoDesignatedContactRetriever;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Application\Port\DesignatedContactRetriever;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Tests\App\Base\ApiTestCase;

/**
 * The contacts of an account a key designates, read in what the repository writes, without restoring them
 * (ADR 0088).
 */
class DynamoDesignatedContactRetrieverTest extends ApiTestCase
{
    private const string PROFILE = 'https://www.linkedin.com/in/ada-lovelace';

    private AccountId $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
    }

    public function testALinkDesignatesTheContactHoldingIt(): void
    {
        $ada = $this->aContact('Ada', 'Lovelace', new ContactDetails(), ExternalReferences::designating([self::PROFILE], Designated::Person));

        $this->assertSame($ada->id()->value, $this->retriever()->withReference($this->account, self::profile())?->value);
    }

    public function testAnEmailDesignatesTheContactHavingIt(): void
    {
        $ada = $this->aContact('Ada', 'Lovelace', new ContactDetails(new Email('ada@acme.test')));

        $this->assertSame($ada->id()->value, $this->retriever()->withEmail($this->account, new Email('ada@acme.test'))?->value);
    }

    public function testAPhoneNumberDesignatesEveryContactHavingIt(): void
    {
        $grace = $this->aContact('Grace', 'Hopper', new ContactDetails(phoneNumber: PhoneNumber::of('06 12 34 56 78')));
        $alan = $this->aContact('Alan', 'Turing', new ContactDetails(phoneNumber: PhoneNumber::of('06 12 34 56 78')));

        $designated = $this->retriever()->withPhoneNumber($this->account, PhoneNumber::of('+33612345678'));

        $this->assertEqualsCanonicalizing([$grace->id()->value, $alan->id()->value], array_map(static fn (PersonId $id): string => $id->value, $designated));
    }

    public function testTheContactsNamedAlikeShareTheirResemblanceKey(): void
    {
        $ada = $this->aContact('Ada', 'Lovelace', new ContactDetails());
        $homonym = $this->aContact('ADA', 'lovelace', new ContactDetails());

        $resembling = $this->retriever()->resembling($this->account, PersonResemblanceKey::of(new PersonName('Ada', 'Lovelace')) ?? throw new \LogicException());

        $this->assertEqualsCanonicalizing([$ada->id()->value, $homonym->id()->value], array_map(static fn (PersonId $id): string => $id->value, $resembling));
    }

    public function testTheKeyOfAContactOfAnotherAccountDesignatesNobody(): void
    {
        $this->aContact('Ada', 'Lovelace', new ContactDetails(new Email('ada@acme.test')), ExternalReferences::designating([self::PROFILE], Designated::Person));

        $anotherAccount = new AccountId(self::newIdentity());

        $this->assertSame([null, null], [$this->retriever()->withReference($anotherAccount, self::profile()), $this->retriever()->withEmail($anotherAccount, new Email('ada@acme.test'))]);
    }

    /**
     * Built on the table of PersonalData: no use case asks it yet in the container.
     */
    private function retriever(): DesignatedContactRetriever
    {
        $table = self::getContainer()->get('app.table.personal_data');
        $this->assertInstanceOf(Table::class, $table);

        return new DynamoDesignatedContactRetriever($table);
    }

    private static function profile(): ExternalReference
    {
        return ExternalReference::designating(self::PROFILE, Designated::Person);
    }

    private function aContact(string $firstName, string $lastName, ContactDetails $contactDetails, ?ExternalReferences $references = null): Person
    {
        $contact = Person::collect(new PersonId(self::newIdentity()), $this->account, Purpose::Prospecting, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS), new PersonName($firstName, $lastName), $contactDetails);
        $contact->changeContactDetails($contactDetails, $references ?? ExternalReferences::none(), $this->readerWithTheRight(), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        self::getContainer()->get(PersonRepository::class)->save($contact);

        return $contact;
    }

    private function readerWithTheRight(): Reader
    {
        return new Reader(new MemberId(self::newIdentity()), $this->account, new PersonId(self::newIdentity()), true);
    }
}

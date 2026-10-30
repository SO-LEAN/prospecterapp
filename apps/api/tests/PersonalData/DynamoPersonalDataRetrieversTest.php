<?php

namespace Tests\App\PersonalData;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\ContactPageRetriever;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\ContactsIntent;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Tests\App\Base\ApiTestCase;

/**
 * The cards of the persons and the pages of contacts, read from the items the repository writes without
 * restoring the persons, as the reader may read them (ADR 0031, 0088).
 */
class DynamoPersonalDataRetrieversTest extends ApiTestCase
{
    private AccountId $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = new AccountId(self::newIdentity());
    }

    public function testAMemberWithTheRightReadsAContactInClearAndMayDoEverything(): void
    {
        $ada = $this->aContact('Ada', 'Lovelace');

        $card = $this->cardOf($ada, $this->readerWithTheRight());

        $this->assertSame([false, 'Ada', 'Lovelace', 'ada@lovelace.test', '+33 3 88 00 00 00'], [$card->masked, $card->firstName, $card->lastName, $card->email, $card->phoneNumber]);
        $this->assertNotNull($card->picture);
        $this->assertSame(self::names(PersonIntent::cases()), $card->possibleIntents);
    }

    public function testAMemberWithoutTheRightReadsAContactMaskedAndMayDoNothing(): void
    {
        $card = $this->cardOf($this->aContact('Ada', 'Lovelace'), $this->readerWithoutTheRight());

        $this->assertTrue($card->masked);
        $this->assertNotSame('Lovelace', $card->lastName);
        $this->assertNull($card->picture);
        $this->assertSame([], $card->possibleIntents);
    }

    public function testThePersonReadsThemselfInClearAndChangesTheirDataButIsNotForgotten(): void
    {
        $me = Person::collect(new PersonId(self::newIdentity()), $this->account, Purpose::Membership, new \DateTimeImmutable(), new PersonName('Grace', 'Hopper'));
        $this->persons()->save($me);

        $card = $this->cardOf($me, new Reader(new MemberId(self::newIdentity()), $this->account, $me->id(), false));

        $this->assertSame([false, 'Hopper'], [$card->masked, $card->lastName]);
        $this->assertSame(self::names([PersonIntent::Rename, PersonIntent::ChangeJobTitle, PersonIntent::ChangeContactDetails, PersonIntent::ChangePicture]), $card->possibleIntents);
    }

    public function testAnotherAccountDoesNotEvenLearnThatThePersonExists(): void
    {
        $ada = $this->aContact('Ada', 'Lovelace');
        $stranger = new Reader(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), new PersonId(self::newIdentity()), true);

        $this->assertSame([], $this->cards()->ofIds([$ada->id()], $stranger));
        $this->expectException(UnknownPerson::class);

        $this->cards()->get($ada->id(), $stranger);
    }

    public function testTheCardsOfSeveralPersonsAreThoseThatExist(): void
    {
        $ada = $this->aContact('Ada', 'Lovelace');

        $cards = $this->cards()->ofIds([$ada->id(), new PersonId(self::newIdentity())], $this->readerWithTheRight());

        $this->assertSame([$ada->id()->value], array_map(static fn (PersonCard $card): string => $card->id, $cards));
    }

    public function testACallingContactIsRecognizedByTheirPhoneNumberInTheAccount(): void
    {
        $ada = $this->aContact('Ada', 'Lovelace');

        $calling = $this->cards()->contactsWithPhoneNumber(PhoneNumber::of('03 88 00 00 00'), $this->readerWithTheRight());

        $this->assertSame([$ada->id()->value], array_map(static fn (PersonCard $card): string => $card->id, $calling));
    }

    public function testTheContactsComeInSlicesFoundByTheBeginningOfTheirName(): void
    {
        $this->aContact('Ada', 'Lovelace');
        $grace = $this->aContact('Grace', 'Hopper');
        $reader = $this->readerWithTheRight();

        $all = self::getContainer()->get(ContactPageRetriever::class)->sliceFor($reader, null, null, 1);
        $found = self::getContainer()->get(ContactPageRetriever::class)->sliceFor($reader, SearchKey::of('hop'), null, 20);

        $this->assertCount(1, $all->contacts);
        $this->assertNotNull($all->next);
        $this->assertSame([$grace->id()->value], array_map(static fn (PersonCard $card): string => $card->id, $found->contacts));
        $this->assertSame(self::names(ContactsIntent::cases()), $found->possibleIntents);
        $this->assertSame([], self::getContainer()->get(ContactPageRetriever::class)->sliceFor($this->readerWithoutTheRight(), null, null, 20)->possibleIntents);
    }

    private function aContact(string $firstName, string $lastName): Person
    {
        $email = new Email(strtolower($firstName).'@'.strtolower($lastName).'.test');
        $contact = Person::collect(new PersonId(self::newIdentity()), $this->account, Purpose::Prospecting, new \DateTimeImmutable(), new PersonName($firstName, $lastName), new ContactDetails($email, PhoneNumber::of('03 88 00 00 00')));
        $contact->changePicture(new File('https://static.test/'.$lastName.'.png', 'png', 1024), $this->readerWithTheRight(), new \DateTimeImmutable());
        $this->persons()->save($contact);

        return $contact;
    }

    private function cardOf(Person $person, Reader $reader): PersonCard
    {
        return $this->cards()->get($person->id(), $reader);
    }

    private function readerWithTheRight(): Reader
    {
        return new Reader(new MemberId(self::newIdentity()), $this->account, new PersonId(self::newIdentity()), true);
    }

    private function readerWithoutTheRight(): Reader
    {
        return new Reader(new MemberId(self::newIdentity()), $this->account, new PersonId(self::newIdentity()), false);
    }

    /**
     * @param list<\UnitEnum> $intents
     *
     * @return list<string>
     */
    private static function names(array $intents): array
    {
        return array_map(static fn (\UnitEnum $intent): string => $intent->name, $intents);
    }

    private function cards(): PersonCardRetriever
    {
        return self::getContainer()->get(PersonCardRetriever::class);
    }

    private function persons(): PersonRepository
    {
        return self::getContainer()->get(PersonRepository::class);
    }
}

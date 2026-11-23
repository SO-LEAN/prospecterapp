<?php

declare(strict_types=1);

namespace Tests\App\PersonalData;

use App\PersonalData\Persistence\DynamoPersonRepository;
use App\PersonalData\Persistence\PersonDocument;
use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Model\Civility;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\NewContact;
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
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * EmailUniqueness checks the email before saving; when two requests take it at once, the conditional write
 * of its reservation refuses the second one the same way (ADR 0039, 0048).
 */
class DynamoPersonRepositoryTest extends ApiTestCase
{
    public function testTheReservationRefusesAnEmailTakenMeanwhile(): void
    {
        $persons = self::service(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $persons->save($this->personWithTheEmail($account, 'ada@solean-it.io'));

        $this->expectException(EmailAlreadyUsed::class);

        $persons->save($this->personWithTheEmail($account, 'ada@solean-it.io'));
    }

    public function testTheQuestionLeavesThePersonItselfOut(): void
    {
        $persons = self::service(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $ada = $this->personWithTheEmail($account, 'ada@solean-it.io');
        $persons->save($ada);

        $this->assertFalse($persons->isEmailUsed($account, Purpose::Prospecting, new Email('ada@solean-it.io'), except: $ada->id()));
        $this->assertTrue($persons->isEmailUsed($account, Purpose::Prospecting, new Email('ada@solean-it.io'), except: new PersonId(self::newIdentity())));
        $this->assertFalse($persons->isEmailUsed($account, Purpose::Membership, new Email('ada@solean-it.io'), except: new PersonId(self::newIdentity())));
    }

    public function testTheContactsHavingEmailsAreFoundByTheirReservations(): void
    {
        $persons = self::service(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $ada = $this->personWithTheEmail($account, 'ada@solean-it.io');
        $persons->save($ada);
        $persons->save($this->personWithTheEmail(new AccountId(self::newIdentity()), 'grace@solean-it.io'));

        $found = $persons->contactsHavingEmails($account, [new Email('ada@solean-it.io'), new Email('grace@solean-it.io'), new Email('nobody@solean-it.io')]);

        $this->assertEquals(['ada@solean-it.io' => $ada->id()], $found);
    }

    public function testAnErasedPersonFreesItsEmail(): void
    {
        $persons = self::service(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $ada = $this->personWithTheEmail($account, 'ada@solean-it.io');
        $persons->save($ada);

        $persons->remove($ada);

        $this->assertFalse($persons->isEmailUsed($account, Purpose::Prospecting, new Email('ada@solean-it.io'), except: new PersonId(self::newIdentity())));
    }

    public function testAPersonIsReadBackAsItWasSaved(): void
    {
        $persons = self::service(PersonRepository::class);
        $ada = $this->personWithTheEmail(new AccountId(self::newIdentity()), 'ada@solean-it.io');
        $ada->releaseEvents();
        $persons->save($ada);

        $this->assertEquals([$ada], $persons->ofIds([$ada->id(), new PersonId(self::newIdentity())]));
    }

    public function testTheLinksOfAPersonAreReadBackAndAPersonSavedBeforeHasNone(): void
    {
        $persons = self::service(PersonRepository::class);
        $ada = $this->personWithTheEmail(new AccountId(self::newIdentity()), 'ada@solean-it.io');
        $ada->changeContactDetails($ada->contactDetails(), ExternalReferences::designating(['linkedin.com/in/ada', 'https://www.malt.fr/profile/ada'], Designated::Person), by: new Reader(new MemberId(self::newIdentity()), $ada->accountId(), $ada->id(), readsPersonalData: true), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $ada->releaseEvents();
        $persons->save($ada);

        $this->assertEquals([$ada], $persons->ofIds([$ada->id()]));
        $savedBefore = PersonDocument::of($ada);
        unset($savedBefore['references'], $savedBefore['formerReferences']);
        $this->assertSame([], PersonDocument::person($savedBefore)->referencesHeld());
    }

    public function testTheTitleOfAContactIsReadBackAndAPersonSavedBeforeHasAnUnknownOne(): void
    {
        $persons = self::service(PersonRepository::class);
        $camille = $this->personWithTheEmail(new AccountId(self::newIdentity()), 'camille@solean-it.io');
        $camille->changeCivility(Civility::Mr, by: new Reader(new MemberId(self::newIdentity()), $camille->accountId(), new PersonId(self::newIdentity()), readsPersonalData: true), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $camille->releaseEvents();
        $persons->save($camille);

        $this->assertEquals([$camille], $persons->ofIds([$camille->id()]));
        $savedBefore = PersonDocument::of($camille);
        $this->assertSame('mr', $savedBefore['title']);
        unset($savedBefore['title']);
        $this->assertSame(Civility::X, PersonDocument::person($savedBefore)->civility());
    }

    public function testAContactAnAiCollectedIsReadBackCreatedByAnAiAndAPersonSavedBeforeByHand(): void
    {
        $persons = self::service(PersonRepository::class);
        $accountId = new AccountId(self::newIdentity());
        $collector = new Reader(new MemberId(self::newIdentity()), $accountId, new PersonId(self::newIdentity()), readsPersonalData: true);
        $grace = Person::collectAsContact($collector, new NewContact(new PersonId(self::newIdentity()), new ContactCard(new PersonName('Grace', 'Hopper'), new ContactDetails(), Civility::X, null, ExternalReferences::none()), [new OrganizationId(self::newIdentity())]), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS), Origin::Ai);
        $grace->releaseEvents();
        $persons->save($grace);

        $this->assertEquals([$grace], $persons->ofIds([$grace->id()]));
        $savedBefore = PersonDocument::of($grace);
        unset($savedBefore['origin']);
        $this->assertSame(Origin::Hand, Hydrator::stateOf(PersonDocument::person($savedBefore))['origin']);
    }

    public function testAnErasedPersonLeavesNoItemBehind(): void
    {
        $persons = self::service(PersonRepository::class);
        $ada = $this->personWithTheEmail(new AccountId(self::newIdentity()), 'ada@solean-it.io');
        $persons->save($ada);

        $persons->remove($ada);

        $this->assertSame([], $this->personalDataTable()->query('PERSON#'.$ada->id()->value));
    }

    public function testAContactIsFoundByTheEmail(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($ada = $this->personWithTheEmail($account, 'ada@solean-it.io'));

        $this->assertSame($ada->id()->value, $this->persons()->contactWithEmail($account, new Email('ada@solean-it.io'))?->id()->value);
    }

    public function testNoContactOfAnotherAccountIsFoundByTheEmail(): void
    {
        $this->persons()->save($this->personWithTheEmail(new AccountId(self::newIdentity()), 'ada@solean-it.io'));

        $this->assertNull($this->persons()->contactWithEmail(new AccountId(self::newIdentity()), new Email('ada@solean-it.io')));
    }

    public function testNoContactIsFoundByAnotherEmail(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($this->personWithTheEmail($account, 'ada@solean-it.io'));

        $this->assertNull($this->persons()->contactWithEmail($account, new Email('grace@solean-it.io')));
    }

    public function testTheContactsResemblingAnotherAreFoundByTheirKey(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactNamed($account, 'Ada', 'Lovelace');
        $this->persons()->save($ada);

        $this->assertEquals([$ada->id()], $this->persons()->resembling($account, self::resemblanceKeyOf($this->contactNamed($account, 'ada', 'LOVELACE')), except: new PersonId(self::newIdentity())));
    }

    public function testTheProfileOfAMemberNamedLikeAContactResemblesNoContact(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save(Person::collect(new Gesture($account, new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new PersonId(self::newIdentity()), Purpose::Membership, new ContactCard(new PersonName('Ada', 'Lovelace'))));

        $this->assertSame([], $this->persons()->resembling($account, self::resemblanceKeyOf($this->contactNamed($account, 'Ada', 'Lovelace')), except: new PersonId(self::newIdentity())));
    }

    public function testAForgottenContactResemblesNoOtherOne(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactNamed($account, 'Ada', 'Lovelace');
        $this->persons()->save($ada);

        $this->persons()->remove($ada);

        $this->assertSame([], $this->persons()->resembling($account, self::resemblanceKeyOf($ada), except: new PersonId(self::newIdentity())));
    }

    public function testARenamedContactNoLongerResemblesThoseOfItsFormerName(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactNamed($account, 'Ada', 'Lovelace');
        $this->persons()->save($ada);
        $formerKey = self::resemblanceKeyOf($ada);

        $ada->rename(new PersonName('Grace', 'Hopper'), by: new Reader(new MemberId(self::newIdentity()), $account, new PersonId(self::newIdentity()), readsPersonalData: true), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->persons()->save($ada);

        $this->assertSame([], $this->persons()->resembling($account, $formerKey, except: new PersonId(self::newIdentity())));
    }

    public function testAContactResemblingAnotherIsReadBackWithTheirPossibleDuplicates(): void
    {
        $ada = $this->contactNamed(new AccountId(self::newIdentity()), 'Ada', 'Lovelace');
        $ada->suspectDuplicateOf(new PersonId(self::newIdentity()), by: new MemberId(self::newIdentity()), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $ada->releaseEvents();
        $this->persons()->save($ada);

        $this->assertEquals($ada, $this->persons()->get($ada->id()));
    }

    public function testTheKeysWrittenAgainLinkAContactToThoseResemblingThem(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactNamed($account, 'Ada', 'Lovelace');
        $this->persons()->save($ada);
        $this->personalDataTable()->batch([], [[sprintf('RESEMBLANCE#%s#%s', $account->value, $ada->resemblanceKey()), 'PERSON#'.$ada->id()]]);

        self::service(DynamoPersonRepository::class)->writeTheKeysAgain();

        $this->assertEquals([$ada->id()], $this->persons()->resembling($account, self::resemblanceKeyOf($ada), except: new PersonId(self::newIdentity())));
    }

    public function testAContactIsReadBackWithTheContactsTheyAreNotADuplicateOf(): void
    {
        $ada = $this->contactNamed(new AccountId(self::newIdentity()), 'Ada', 'Lovelace');
        $ada->knowItIsNoDuplicateOf(new PersonId(self::newIdentity()), by: new MemberId(self::newIdentity()), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $ada->releaseEvents();
        $this->persons()->save($ada);

        $this->assertEquals($ada, $this->persons()->get($ada->id()));
    }

    public function testAPersonChangedSinceItWasReadIsNotSavedOverTheChange(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactNamed($account, 'Ada', 'Lovelace');
        $this->persons()->save($ada);
        $unitOfWork = self::service(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $read = $this->persons()->get($ada->id());
        $this->anotherRequestRenames($ada->id(), new PersonName('Ada', 'King'), $account);
        $read->rename(new PersonName('Augusta', 'Lovelace'), by: $this->readerIn($account), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->persons()->save($read);

        try {
            $unitOfWork->commit();
            $this->fail('The save was expected to be cancelled');
        } catch (ConditionFailed) {
            $unitOfWork->discard();
        }

        $this->assertEquals(new PersonName('Ada', 'King'), $this->persons()->get($ada->id())->name());
    }

    public function testAPersonChangedSinceItWasReadIsNotRemovedOverTheChange(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactNamed($account, 'Ada', 'Lovelace');
        $this->persons()->save($ada);
        $unitOfWork = self::service(DynamoUnitOfWork::class);
        $unitOfWork->begin();
        $read = $this->persons()->get($ada->id());
        $this->anotherRequestRenames($ada->id(), new PersonName('Ada', 'King'), $account);
        $this->persons()->remove($read);

        try {
            $unitOfWork->commit();
            $this->fail('The removal was expected to be cancelled');
        } catch (ConditionFailed) {
            $unitOfWork->discard();
        }

        $this->assertEquals(new PersonName('Ada', 'King'), $this->persons()->get($ada->id())->name());
    }

    private function anotherRequestRenames(PersonId $person, PersonName $name, AccountId $account): void
    {
        $persons = new DynamoPersonRepository($this->asAnotherRequest($this->personalDataTable()));
        $renaming = $persons->get($person);
        $renaming->rename($name, by: $this->readerIn($account), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $persons->save($renaming);
    }

    private function readerIn(AccountId $account): Reader
    {
        return new Reader(new MemberId(self::newIdentity()), $account, new PersonId(self::newIdentity()), readsPersonalData: true);
    }

    private function contactNamed(AccountId $account, string $firstName, string $lastName): Person
    {
        return Person::collect(new Gesture($account, new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new PersonId(self::newIdentity()), Purpose::Prospecting, new ContactCard(new PersonName($firstName, $lastName)));
    }

    private function persons(): PersonRepository
    {
        return self::service(PersonRepository::class);
    }

    private function personalDataTable(): Table
    {
        return self::tableOf('personal_data');
    }

    private static function resemblanceKeyOf(Person $contact): PersonResemblanceKey
    {
        $key = $contact->resemblanceKey();
        self::assertNotNull($key);

        return $key;
    }

    private function personWithTheEmail(AccountId $account, string $email): Person
    {
        return Person::collect(new Gesture($account, new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new PersonId(self::newIdentity()), Purpose::Prospecting, new ContactCard(contactDetails: new ContactDetails(new Email($email))));
    }
}

<?php

namespace Tests\App\PersonalData;

use App\PersonalData\Persistence\PersonDocument;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Model\Civility;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Tests\App\Base\ApiTestCase;

/**
 * EmailUniqueness checks the email before saving; when two requests take it at once, the conditional write
 * of its reservation refuses the second one the same way (ADR 0039, 0048).
 */
class DynamoPersonRepositoryTest extends ApiTestCase
{
    public function testTheReservationRefusesAnEmailTakenMeanwhile(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $persons->save($this->personWithTheEmail($account, 'ada@solean-it.io'));

        $this->expectException(EmailAlreadyUsed::class);

        $persons->save($this->personWithTheEmail($account, 'ada@solean-it.io'));
    }

    public function testTheQuestionLeavesThePersonItselfOut(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $ada = $this->personWithTheEmail($account, 'ada@solean-it.io');
        $persons->save($ada);

        $this->assertFalse($persons->isEmailUsed($account, Purpose::Prospecting, new Email('ada@solean-it.io'), except: $ada->id()));
        $this->assertTrue($persons->isEmailUsed($account, Purpose::Prospecting, new Email('ada@solean-it.io'), except: new PersonId(self::newIdentity())));
        $this->assertFalse($persons->isEmailUsed($account, Purpose::Membership, new Email('ada@solean-it.io'), except: new PersonId(self::newIdentity())));
    }

    public function testTheContactsHavingEmailsAreFoundByTheirReservations(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $ada = $this->personWithTheEmail($account, 'ada@solean-it.io');
        $persons->save($ada);
        $persons->save($this->personWithTheEmail(new AccountId(self::newIdentity()), 'grace@solean-it.io'));

        $found = $persons->contactsHavingEmails($account, [new Email('ada@solean-it.io'), new Email('grace@solean-it.io'), new Email('nobody@solean-it.io')]);

        $this->assertEquals(['ada@solean-it.io' => $ada->id()], $found);
    }

    public function testAnErasedPersonFreesItsEmail(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $account = new AccountId(self::newIdentity());
        $ada = $this->personWithTheEmail($account, 'ada@solean-it.io');
        $persons->save($ada);

        $persons->remove($ada);

        $this->assertFalse($persons->isEmailUsed($account, Purpose::Prospecting, new Email('ada@solean-it.io'), except: new PersonId(self::newIdentity())));
    }

    public function testAPersonIsReadBackAsItWasSaved(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $ada = $this->personWithTheEmail(new AccountId(self::newIdentity()), 'ada@solean-it.io');
        $ada->releaseEvents();
        $persons->save($ada);

        $this->assertEquals([$ada], $persons->ofIds([$ada->id(), new PersonId(self::newIdentity())]));
    }

    public function testTheLinksOfAPersonAreReadBackAndAPersonSavedBeforeHasNone(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $ada = $this->personWithTheEmail(new AccountId(self::newIdentity()), 'ada@solean-it.io');
        $ada->changeContactDetails($ada->contactDetails(), ExternalReferences::designating(['linkedin.com/in/ada', 'https://www.malt.fr/profile/ada'], Designated::Person), by: new Reader(new MemberId(self::newIdentity()), $ada->accountId(), $ada->id(), readsPersonalData: true), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $ada->releaseEvents();
        $persons->save($ada);

        $this->assertEquals([$ada], $persons->ofIds([$ada->id()]));
        $savedBefore = PersonDocument::of($ada);
        unset($savedBefore['references'], $savedBefore['formerReferences']);
        $this->assertSame([], PersonDocument::person($savedBefore)->referencesHeld());
    }

    public function testTheCivilityOfAContactIsReadBackAndAPersonSavedBeforeHasAnUnknownOne(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $camille = $this->personWithTheEmail(new AccountId(self::newIdentity()), 'camille@solean-it.io');
        $camille->changeCivility(Civility::Mr, by: new Reader(new MemberId(self::newIdentity()), $camille->accountId(), new PersonId(self::newIdentity()), readsPersonalData: true), at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $camille->releaseEvents();
        $persons->save($camille);

        $this->assertEquals([$camille], $persons->ofIds([$camille->id()]));
        $savedBefore = PersonDocument::of($camille);
        unset($savedBefore['civility']);
        $this->assertSame(Civility::X, PersonDocument::person($savedBefore)->civility());
    }

    public function testAnErasedPersonLeavesNoItemBehind(): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
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

    public function testAContactIsFoundByThePhoneNumber(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($ada = $this->personWithThePhoneNumber($account, '06 12 34 56 78'));

        $this->assertSame($ada->id()->value, $this->persons()->contactWithPhoneNumber($account, PhoneNumber::of('+33612345678'))?->id()->value);
    }

    public function testNoContactOfAnotherAccountIsFoundByThePhoneNumber(): void
    {
        $this->persons()->save($this->personWithThePhoneNumber(new AccountId(self::newIdentity()), '06 12 34 56 78'));

        $this->assertNull($this->persons()->contactWithPhoneNumber(new AccountId(self::newIdentity()), PhoneNumber::of('+33612345678')));
    }

    public function testNoContactIsFoundByAnotherPhoneNumber(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($this->personWithThePhoneNumber($account, '06 12 34 56 78'));

        $this->assertNull($this->persons()->contactWithPhoneNumber($account, PhoneNumber::of('+33698765432')));
    }

    private function persons(): PersonRepository
    {
        return self::getContainer()->get(PersonRepository::class);
    }

    private function personWithThePhoneNumber(AccountId $account, string $phoneNumber): Person
    {
        return Person::collect(new PersonId(self::newIdentity()), $account, Purpose::Prospecting, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS), contactDetails: new ContactDetails(phoneNumber: PhoneNumber::of($phoneNumber)));
    }

    private function personalDataTable(): Table
    {
        $table = self::getContainer()->get('app.table.personal_data');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }

    private function personWithTheEmail(AccountId $account, string $email): Person
    {
        return Person::collect(new PersonId(self::newIdentity()), $account, Purpose::Prospecting, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS), contactDetails: new ContactDetails(new Email($email)));
    }
}

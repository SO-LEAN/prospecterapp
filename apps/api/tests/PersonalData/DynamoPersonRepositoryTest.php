<?php

namespace Tests\App\PersonalData;

use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
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

    private function personWithTheEmail(AccountId $account, string $email): Person
    {
        return Person::collect(new PersonId(self::newIdentity()), $account, Purpose::Prospecting, new \DateTimeImmutable(), contactDetails: new ContactDetails(new Email($email)));
    }
}

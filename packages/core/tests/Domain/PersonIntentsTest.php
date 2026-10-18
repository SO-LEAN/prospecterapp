<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonIntent;
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Like the organizations and the accounts: a person refuses a change exactly when it does not offer it, and
 * offers it to the person themself, or to a member with the right for a person their account prospects.
 */
class PersonIntentsTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string OTHER_ACCOUNT = '0190a1c0-0000-7000-8000-000000000002';
    private const string PERSON = '0190a1c0-0000-7000-8000-000000000003';
    private const string COLLEAGUE = '0190a1c0-0000-7000-8000-000000000004';

    #[DataProvider('situations')]
    public function testAnIntentionIsRefusedExactlyWhenItIsNotOffered(PersonIntent $intent, Purpose $purpose, Reader $reader): void
    {
        $person = Person::collect(new PersonId(self::PERSON), new AccountId(self::ACCOUNT), $purpose, new \DateTimeImmutable());
        $offered = in_array($intent, $person->possibleIntentsOf($reader), true);

        $accepted = $this->isAccepted($intent, $person, $reader);

        $this->assertSame($offered, $accepted);
        $this->assertSame($accepted, [] !== $person->releaseEvents(), 'an accepted intention records what happened, a refused one nothing');
    }

    /**
     * @return iterable<string, array{PersonIntent, Purpose, Reader}>
     */
    public static function situations(): iterable
    {
        $readers = [
            'the person' => new Reader(self::member(), new AccountId(self::ACCOUNT), new PersonId(self::PERSON), false),
            'a colleague with the right' => new Reader(self::member(), new AccountId(self::ACCOUNT), new PersonId(self::COLLEAGUE), true),
            'a colleague without the right' => new Reader(self::member(), new AccountId(self::ACCOUNT), new PersonId(self::COLLEAGUE), false),
            'another account' => new Reader(self::member(), new AccountId(self::OTHER_ACCOUNT), new PersonId(self::COLLEAGUE), true),
        ];

        foreach (PersonIntent::cases() as $intent) {
            foreach (Purpose::cases() as $purpose) {
                foreach ($readers as $who => $reader) {
                    yield sprintf('%s of a %s person by %s', $intent->name, $purpose->name, $who) => [$intent, $purpose, $reader];
                }
            }
        }
    }

    public function testAColleagueWithTheRightChangesAProspectedPersonButNotAColleague(): void
    {
        $withTheRight = new Reader(self::member(), new AccountId(self::ACCOUNT), new PersonId(self::COLLEAGUE), true);

        $this->assertSame(PersonIntent::cases(), Person::collect(new PersonId(self::PERSON), new AccountId(self::ACCOUNT), Purpose::Prospecting, new \DateTimeImmutable())->possibleIntentsOf($withTheRight));
        $this->assertSame([], Person::collect(new PersonId(self::PERSON), new AccountId(self::ACCOUNT), Purpose::Membership, new \DateTimeImmutable())->possibleIntentsOf($withTheRight));
    }

    private static function member(): MemberId
    {
        return new MemberId('0190a1c0-0000-7000-8000-0000000000aa');
    }

    private function isAccepted(PersonIntent $intent, Person $person, Reader $by): bool
    {
        try {
            match ($intent) {
                PersonIntent::Rename => $person->rename(new PersonName('Ada'), $by, new \DateTimeImmutable()),
                PersonIntent::ChangeJobTitle => $person->changeJobTitle('Directrice commerciale', $by, new \DateTimeImmutable()),
                PersonIntent::ChangeContactDetails => $person->changeContactDetails(new ContactDetails(phoneNumber: PhoneNumber::of('03 88 00 00 00')), $by, new \DateTimeImmutable()),
                PersonIntent::ChangePicture => $person->changePicture(new File('https://storage.test/ada.png', 'png', 1024), $by, new \DateTimeImmutable()),
                PersonIntent::Forget => $person->forget($by, new \DateTimeImmutable()),
            };
        } catch (NotFound|BusinessRuleViolation) {
            return false;
        }

        return true;
    }
}

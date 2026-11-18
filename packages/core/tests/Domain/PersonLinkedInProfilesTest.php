<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Domain\Model\LinkedInProfile;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person renames the address of their profile on LinkedIn: the profiles they had still recognize them, the last
 * three of them.
 */
class PersonLinkedInProfilesTest extends TestCase
{
    private const string AT = '2026-11-18 09:00 UTC';
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string PERSON = '0190a1c0-0000-7000-8000-000000000003';

    public function testAPersonStillHoldsTheProfileTheyHadBeforeChangingIt(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->noteTheProfile($ada, 'ada-l');

        $this->assertTrue($ada->holdsLinkedInProfile(self::profile('ada-lovelace')));
    }

    public function testAPersonHoldsTheirCurrentProfile(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->assertTrue($ada->holdsLinkedInProfile(self::profile('ada-lovelace')));
    }

    public function testAPersonKeepsTheirLastThreeFormerProfilesOnly(): void
    {
        $ada = $this->personWithTheProfile('ada-1');

        foreach (['ada-2', 'ada-3', 'ada-4', 'ada-5'] as $identifier) {
            $this->noteTheProfile($ada, $identifier);
        }

        $this->assertEquals(array_map(self::profile(...), ['ada-5', 'ada-4', 'ada-3', 'ada-2']), $ada->linkedInProfilesHeld());
    }

    public function testTheSameProfileNotedAgainIsNoFormerOne(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->noteTheProfile($ada, 'ada-lovelace');

        $this->assertEquals([self::profile('ada-lovelace')], $ada->linkedInProfilesHeld());
    }

    public function testAFormerProfileNotedBackIsTheCurrentOneAgain(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');
        $this->noteTheProfile($ada, 'ada-l');

        $this->noteTheProfile($ada, 'ada-lovelace');

        $this->assertEquals(array_map(self::profile(...), ['ada-lovelace', 'ada-l']), $ada->linkedInProfilesHeld());
    }

    public function testAProfileTakenOffStaysAFormerOne(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->noteTheProfile($ada, null);

        $this->assertNull($ada->linkedInProfile());
        $this->assertTrue($ada->holdsLinkedInProfile(self::profile('ada-lovelace')));
    }

    private function personWithTheProfile(string $identifier): Person
    {
        $person = Person::collect(new PersonId(self::PERSON), new AccountId(self::ACCOUNT), Purpose::Prospecting, new \DateTimeImmutable(self::AT));
        $this->noteTheProfile($person, $identifier);

        return $person;
    }

    private function noteTheProfile(Person $person, ?string $identifier): void
    {
        $manager = new Reader(new MemberId('0190a1c0-0000-7000-8000-000000000009'), new AccountId(self::ACCOUNT), new PersonId('0190a1c0-0000-7000-8000-000000000008'), true);
        $person->changeContactDetails(new ContactDetails(), null === $identifier ? null : self::profile($identifier), by: $manager, at: new \DateTimeImmutable(self::AT));
    }

    private static function profile(string $identifier): LinkedInProfile
    {
        return new LinkedInProfile('https://www.linkedin.com/in/'.$identifier);
    }
}

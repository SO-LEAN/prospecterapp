<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A person changes their profile's address on a tool, or removes a link. Their former references still recognize
 * them. The last three are kept, whatever their source.
 */
class PersonReferencesTest extends TestCase
{
    private const string AT = '2026-11-18 09:00 UTC';
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string PERSON = '0190a1c0-0000-7000-8000-000000000003';
    private const string ADMINISTRATOR = '0190a1c0-0000-7000-8000-000000000004';

    public function testAPersonStillHoldsTheProfileTheyHadBeforeChangingIt(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->noteTheProfile($ada, 'ada-l');

        $this->assertTrue($ada->holdsReference(self::profile('ada-lovelace')));
    }

    public function testAPersonHoldsTheirCurrentProfile(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->assertTrue($ada->holdsReference(self::profile('ada-lovelace')));
    }

    public function testAPersonKeepsTheirLastThreeFormerProfilesOnly(): void
    {
        $ada = $this->personWithTheProfile('ada-1');

        foreach (['ada-2', 'ada-3', 'ada-4', 'ada-5'] as $identifier) {
            $this->noteTheProfile($ada, $identifier);
        }

        $this->assertEquals(array_map(self::profile(...), ['ada-5', 'ada-4', 'ada-3', 'ada-2']), $ada->referencesHeld());
    }

    public function testTheSameProfileNotedAgainIsNoFormerOne(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->noteTheProfile($ada, 'ada-lovelace');

        $this->assertEquals([self::profile('ada-lovelace')], $ada->referencesHeld());
    }

    public function testAFormerProfileNotedBackIsTheCurrentOneAgain(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');
        $this->noteTheProfile($ada, 'ada-l');

        $this->noteTheProfile($ada, 'ada-lovelace');

        $this->assertEquals(array_map(self::profile(...), ['ada-lovelace', 'ada-l']), $ada->referencesHeld());
    }

    public function testTheFormerReferencesOfEverySourceCountTogether(): void
    {
        $ada = $this->personWithTheProfile('ada-1');

        foreach (['https://www.malt.fr/profile/ada', 'https://github.com/ada', 'https://www.linkedin.com/in/ada-2'] as $address) {
            $this->noteTheLink($ada, $address);
        }
        $this->noteTheProfile($ada, null);

        $this->assertEquals([self::profile('ada-2'), self::reference('https://github.com/ada'), self::reference('https://www.malt.fr/profile/ada')], $ada->referencesHeld());
    }

    public function testAProfileTakenOffStaysAFormerOne(): void
    {
        $ada = $this->personWithTheProfile('ada-lovelace');

        $this->noteTheProfile($ada, null);

        $this->assertSame([], $ada->references()->all());
        $this->assertTrue($ada->holdsReference(self::profile('ada-lovelace')));
    }

    private function personWithTheProfile(string $identifier): Person
    {
        $person = Person::collect(new Gesture(new AccountId(self::ACCOUNT), new MemberId(self::ADMINISTRATOR), new \DateTimeImmutable(self::AT)), new PersonId(self::PERSON), Purpose::Prospecting);
        $this->noteTheProfile($person, $identifier);

        return $person;
    }

    private function noteTheProfile(Person $person, ?string $identifier): void
    {
        $this->noteTheLinks($person, null === $identifier ? [] : ['https://www.linkedin.com/in/'.$identifier]);
    }

    private function noteTheLink(Person $person, string $address): void
    {
        $this->noteTheLinks($person, [$address]);
    }

    /**
     * @param list<string> $addresses
     */
    private function noteTheLinks(Person $person, array $addresses): void
    {
        $manager = new Reader(new MemberId('0190a1c0-0000-7000-8000-000000000009'), new AccountId(self::ACCOUNT), new PersonId('0190a1c0-0000-7000-8000-000000000008'), true);
        $person->changeContactDetails(new ContactDetails(), ExternalReferences::designating($addresses, Designated::Person), by: $manager, at: new \DateTimeImmutable(self::AT));
    }

    private static function profile(string $identifier): ExternalReference
    {
        return self::reference('https://www.linkedin.com/in/'.$identifier);
    }

    private static function reference(string $address): ExternalReference
    {
        return ExternalReference::designating($address, Designated::Person);
    }
}

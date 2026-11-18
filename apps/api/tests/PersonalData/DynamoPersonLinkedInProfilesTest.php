<?php

namespace Tests\App\PersonalData;

use App\PersonalData\Persistence\PersonDocument;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Exception\LinkedInProfileAlreadyUsed;
use Prospecter\Core\PersonalData\Domain\Model\LinkedInProfile;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * LinkedInProfileUniqueness asks which contact of an account holds a profile, current or former; the reservation of
 * each profile a contact holds answers it, and refuses a second holder when two requests take it at once (ADR 0039,
 * 0048).
 */
class DynamoPersonLinkedInProfilesTest extends ApiTestCase
{
    private const string ADA = 'https://www.linkedin.com/in/ada-lovelace';

    public function testTheContactHoldingAProfileIsFound(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA);
        $this->persons()->save($ada);

        $this->assertEquals($ada->id(), $this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testTheFormerProfilesOfAContactAreReadBack(): void
    {
        $ada = $this->contactWithTheProfiles(new AccountId(self::newIdentity()), self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);

        $this->assertEquals($ada, $this->persons()->get($ada->id()));
    }

    public function testAPersonSavedBeforeTheFormerProfilesHasNone(): void
    {
        $ada = $this->contactWithTheProfiles(new AccountId(self::newIdentity()), self::ADA);
        $savedBefore = PersonDocument::of($ada);
        unset($savedBefore['formerLinkedInProfiles']);

        $this->assertEquals([new LinkedInProfile(self::ADA)], PersonDocument::person($savedBefore)->linkedInProfilesHeld());
    }

    public function testAFormerProfileStillFindsItsContactButNotForThemself(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);

        $this->assertEquals($ada->id(), $this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity()))?->id());
        $this->assertNull($this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: $ada->id()));
        $this->assertNull($this->persons()->contactHoldingLinkedInProfile(new AccountId(self::newIdentity()), new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity())));
    }

    public function testTheReservationRefusesAProfileTakenMeanwhile(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($this->contactWithTheProfiles($account, self::ADA));

        $this->expectException(LinkedInProfileAlreadyUsed::class);

        $this->persons()->save($this->contactWithTheProfiles($account, self::ADA));
    }

    public function testTheProfileOfAMemberIsReservedForNobody(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($this->personWithTheProfiles($account, Purpose::Membership, self::ADA));

        $this->persons()->save($this->personWithTheProfiles($account, Purpose::Membership, self::ADA));

        $this->assertNull($this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity())));
    }

    public function testAnErasedContactFreesTheirProfiles(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);

        $this->persons()->remove($ada);

        $this->assertNull($this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity())));
        $this->assertNull($this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile('https://www.linkedin.com/in/ada-l'), except: new PersonId(self::newIdentity())));
    }

    public function testErasingTheYoungerOfTwoDuplicatesKeepsTheReservationOfTheOldest(): void
    {
        $account = new AccountId(self::newIdentity());
        [$oldest, $younger] = $this->duplicatesOf($account, self::ADA);

        $this->persons()->remove($younger);

        $this->assertEquals($oldest->id(), $this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testAProfileNoLongerAmongTheFormerOnesIsFreed(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA);
        $this->persons()->save($ada);

        $this->noteTheProfiles($ada, 'https://www.linkedin.com/in/ada-2', 'https://www.linkedin.com/in/ada-3', 'https://www.linkedin.com/in/ada-4', 'https://www.linkedin.com/in/ada-5');
        $this->persons()->save($ada);

        $this->assertNull($this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity())));
    }

    public function testTheYoungerOfTwoDuplicatesDroppingTheirProfileKeepsTheReservationOfTheOldest(): void
    {
        $account = new AccountId(self::newIdentity());
        [$oldest, $younger] = $this->duplicatesOf($account, self::ADA);

        $this->noteTheProfiles($younger, 'https://www.linkedin.com/in/grace-2', 'https://www.linkedin.com/in/grace-3', 'https://www.linkedin.com/in/grace-4', 'https://www.linkedin.com/in/grace-5');
        $this->persons()->save($younger);

        $this->assertEquals($oldest->id(), $this->persons()->contactHoldingLinkedInProfile($account, new LinkedInProfile(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    /**
     * Two contacts noted the same profile before it was unique, the oldest holding its reservation as the rebuild of
     * the keys leaves it.
     *
     * @return array{Person, Person}
     */
    private function duplicatesOf(AccountId $account, string $profile): array
    {
        $oldest = $this->contactWithTheProfiles($account, $profile);
        $younger = $this->contactWithTheProfiles($account, $profile);
        $this->persons()->save($younger);
        $this->personalDataTable()->batch([['PK' => sprintf('LINKEDIN#%s#%s', $account->value, $profile), 'SK' => 'LINKEDIN', 'ownerId' => $oldest->id()->value]]);
        $this->persons()->save($oldest);

        return [$oldest, $younger];
    }

    private function personalDataTable(): Table
    {
        $table = self::getContainer()->get('app.table.personal_data');
        $this->assertInstanceOf(Table::class, $table);

        return $table;
    }

    private function contactWithTheProfiles(AccountId $account, string ...$profiles): Person
    {
        return $this->personWithTheProfiles($account, Purpose::Prospecting, ...$profiles);
    }

    private function personWithTheProfiles(AccountId $account, Purpose $purpose, string ...$profiles): Person
    {
        $person = Person::collect(new PersonId(self::newIdentity()), $account, $purpose, new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        $this->noteTheProfiles($person, ...$profiles);

        return $person;
    }

    private function noteTheProfiles(Person $person, string ...$profiles): void
    {
        $themself = new Reader(new MemberId(self::newIdentity()), $person->accountId(), $person->id(), readsPersonalData: false);

        foreach ($profiles as $profile) {
            $person->changeContactDetails($person->contactDetails(), new LinkedInProfile($profile), by: $themself, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        }
        $person->releaseEvents();
    }

    private function persons(): PersonRepository
    {
        return self::getContainer()->get(PersonRepository::class);
    }
}

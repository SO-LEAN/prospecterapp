<?php

namespace Tests\App\PersonalData;

use App\PersonalData\Persistence\DynamoPersonRepository;
use App\PersonalData\Persistence\PersonalDataKeys;
use App\PersonalData\Persistence\PersonDocument;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Exception\ReferenceTakenMeanwhile;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * ReferenceUniqueness asks which contact of an account holds a link, current or former; the reservation of each
 * reference a contact holds answers it, and refuses a second holder when two requests take it at once (ADR 0039,
 * 0048, 0159).
 */
class DynamoPersonReferencesTest extends ApiTestCase
{
    private const string ADA = 'https://www.linkedin.com/in/ada-lovelace';

    public function testTheContactHoldingAProfileIsFound(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA);
        $this->persons()->save($ada);

        $this->assertEquals($ada->id(), $this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testTheFormerProfilesOfAContactAreReadBack(): void
    {
        $ada = $this->contactWithTheProfiles(new AccountId(self::newIdentity()), self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);

        $this->assertEquals($ada, $this->persons()->get($ada->id()));
    }

    public function testAPersonSavedBeforeTheLinksHasTheirLinkedInProfilesAsLinks(): void
    {
        $ada = $this->contactWithTheProfiles(new AccountId(self::newIdentity()), self::ADA, 'https://www.linkedin.com/in/ada-l');

        $this->assertEquals($ada->referencesHeld(), PersonDocument::person(self::savedBeforeTheLinks(PersonDocument::of($ada)))->referencesHeld());
    }

    public function testAPersonSavedBeforeTheFormerProfilesHasNone(): void
    {
        $ada = $this->contactWithTheProfiles(new AccountId(self::newIdentity()), self::ADA);
        $savedBefore = self::savedBeforeTheLinks(PersonDocument::of($ada));
        unset($savedBefore['formerLinkedInProfiles']);

        $this->assertEquals([self::reference(self::ADA)], PersonDocument::person($savedBefore)->referencesHeld());
    }

    public function testAFormerProfileStillFindsItsContact(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);

        $this->assertEquals($ada->id(), $this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testAContactHoldingAProfileIsNoOtherHolderForThemself(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA);
        $this->persons()->save($ada);

        $this->assertNull($this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: $ada->id()));
    }

    public function testAProfileHeldInAnotherAccountIsHeldByNobodyHere(): void
    {
        $ada = $this->contactWithTheProfiles(new AccountId(self::newIdentity()), self::ADA);
        $this->persons()->save($ada);

        $this->assertNull($this->persons()->contactHoldingReference(new AccountId(self::newIdentity()), self::reference(self::ADA), except: new PersonId(self::newIdentity())));
    }

    public function testTheReservationRefusesAProfileTakenMeanwhile(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($this->contactWithTheProfiles($account, self::ADA));

        $this->expectException(ReferenceTakenMeanwhile::class);

        $this->persons()->save($this->contactWithTheProfiles($account, self::ADA));
    }

    public function testTheProfileOfAMemberIsReservedForNobody(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($this->personWithTheProfiles($account, Purpose::Membership, self::ADA));

        $this->persons()->save($this->personWithTheProfiles($account, Purpose::Membership, self::ADA));

        $this->assertNull($this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity())));
    }

    public function testAnErasedContactFreesTheirProfiles(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);

        $this->persons()->remove($ada);

        $this->assertNull($this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity())));
        $this->assertNull($this->persons()->contactHoldingReference($account, self::reference('https://www.linkedin.com/in/ada-l'), except: new PersonId(self::newIdentity())));
    }

    public function testErasingTheYoungerOfTwoDuplicatesKeepsTheReservationOfTheOldest(): void
    {
        $account = new AccountId(self::newIdentity());
        [$oldest, $younger] = $this->duplicatesOf($account, self::ADA);

        $this->persons()->remove($younger);

        $this->assertEquals($oldest->id(), $this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testAProfileNoLongerAmongTheFormerOnesIsFreed(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA);
        $this->persons()->save($ada);

        $this->noteTheProfiles($ada, 'https://www.linkedin.com/in/ada-2', 'https://www.linkedin.com/in/ada-3', 'https://www.linkedin.com/in/ada-4', 'https://www.linkedin.com/in/ada-5');
        $this->persons()->save($ada);

        $this->assertNull($this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity())));
    }

    public function testTheYoungerOfTwoDuplicatesDroppingTheirProfileKeepsTheReservationOfTheOldest(): void
    {
        $account = new AccountId(self::newIdentity());
        [$oldest, $younger] = $this->duplicatesOf($account, self::ADA);

        $this->noteTheProfiles($younger, 'https://www.linkedin.com/in/grace-2', 'https://www.linkedin.com/in/grace-3', 'https://www.linkedin.com/in/grace-4', 'https://www.linkedin.com/in/grace-5');
        $this->persons()->save($younger);

        $this->assertEquals($oldest->id(), $this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testTheKeysWrittenAgainReserveTheProfilesOfAContactSavedBefore(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);
        $this->forgetTheReservationsOf($account, self::ADA, 'https://www.linkedin.com/in/ada-l');

        self::getContainer()->get(DynamoPersonRepository::class)->writeTheKeysAgain();

        $this->assertEquals($ada->id(), $this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity()))?->id());
        $this->assertEquals($ada->id(), $this->persons()->contactHoldingReference($account, self::reference('https://www.linkedin.com/in/ada-l'), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testTheKeysWrittenAgainReserveAProfileTwoContactsNotedForTheOldest(): void
    {
        $account = new AccountId(self::newIdentity());
        [$oldest, $younger] = $this->duplicatesOf($account, self::ADA);
        $this->forgetTheReservationsOf($account, self::ADA);
        $this->persons()->save($younger);
        $this->forgetTheReservationsOf($account, self::ADA);

        self::getContainer()->get(DynamoPersonRepository::class)->writeTheKeysAgain();

        $this->assertEquals($oldest->id(), $this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity()))?->id());
    }

    public function testTheKeysWrittenAgainReserveTheLinkedInProfilesOfAContactSavedBeforeTheLinksAsLinks(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA, 'https://www.linkedin.com/in/ada-l');
        $this->persons()->save($ada);
        $this->forgetTheReservationsOf($account, self::ADA, 'https://www.linkedin.com/in/ada-l');
        $item = $this->personalDataTable()->item(PersonalDataKeys::person($ada->id()->value), PersonalDataKeys::PERSON);
        $item['person'] = self::savedBeforeTheLinks($item['person']);
        $formerReservation = sprintf('LINKEDIN#%s#%s', $account->value, self::ADA);
        $this->personalDataTable()->batch([$item, ['PK' => $formerReservation, 'SK' => 'LINKEDIN', 'ownerId' => $ada->id()->value]]);

        self::getContainer()->get(DynamoPersonRepository::class)->writeTheKeysAgain();

        $this->assertEquals($ada->id(), $this->persons()->contactHoldingReference($account, self::reference('https://www.linkedin.com/in/ada-l'), except: new PersonId(self::newIdentity()))?->id());
        $this->assertNull($this->personalDataTable()->item($formerReservation, 'LINKEDIN'));
    }

    public function testTheKeysWrittenAgainRecognizeAgainALinkStoredUnderTheHostOfASourceKnownSince(): void
    {
        $account = new AccountId(self::newIdentity());
        $ada = $this->contactWithTheProfiles($account, self::ADA);
        $this->persons()->save($ada);
        $this->forgetTheReservationsOf($account, self::ADA);
        $item = $this->personalDataTable()->item(PersonalDataKeys::person($ada->id()->value), PersonalDataKeys::PERSON);
        $item['person']['references'] = [['source' => 'linkedin.com', 'identifier' => 'linkedin.com/in/ada-lovelace', 'address' => self::ADA]];
        $formerReservation = sprintf('REFERENCE#%s#linkedin.com#linkedin.com/in/ada-lovelace', $account->value);
        $this->personalDataTable()->batch([$item, ['PK' => $formerReservation, 'SK' => 'REFERENCE', 'ownerId' => $ada->id()->value]]);
        $this->assertSame(['linkedin.com#linkedin.com/in/ada-lovelace'], array_map(static fn (ExternalReference $reference): string => $reference->key(), $this->persons()->get($ada->id())->references()->all()), 'read as stored');

        self::getContainer()->get(DynamoPersonRepository::class)->writeTheKeysAgain();

        $this->assertEquals($ada->id(), $this->persons()->contactHoldingReference($account, self::reference(self::ADA), except: new PersonId(self::newIdentity()))?->id());
        $this->assertNull($this->personalDataTable()->item($formerReservation, 'REFERENCE'));
    }

    public function testTheKeysWrittenAgainReserveNoProfileOfAMember(): void
    {
        $account = new AccountId(self::newIdentity());
        $this->persons()->save($this->personWithTheProfiles($account, Purpose::Membership, self::ADA));

        self::getContainer()->get(DynamoPersonRepository::class)->writeTheKeysAgain();

        $this->assertNull($this->personalDataTable()->item(self::reservationKey($account, self::ADA), 'REFERENCE'));
    }

    private function forgetTheReservationsOf(AccountId $account, string ...$profiles): void
    {
        $this->personalDataTable()->batch([], array_map(static fn (string $profile): array => [self::reservationKey($account, $profile), 'REFERENCE'], $profiles));
    }

    private static function reservationKey(AccountId $account, string $profile): string
    {
        return sprintf('REFERENCE#%s#%s', $account->value, self::reference($profile)->key());
    }

    private static function reference(string $address): ExternalReference
    {
        return ExternalReference::designating($address, Designated::Person);
    }

    /**
     * A document as it was written before the links: the LinkedIn profile and the former ones alone.
     *
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private static function savedBeforeTheLinks(array $document): array
    {
        $document['linkedInProfile'] = $document['references'][0]['address'] ?? null;
        $document['formerLinkedInProfiles'] = array_column($document['formerReferences'], 'address');
        unset($document['references'], $document['formerReferences']);

        return $document;
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
        $this->personalDataTable()->batch([['PK' => self::reservationKey($account, $profile), 'SK' => 'REFERENCE', 'ownerId' => $oldest->id()->value]]);
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
            $person->changeContactDetails($person->contactDetails(), ExternalReferences::designating([$profile], Designated::Person), by: $themself, at: new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        }
        $person->releaseEvents();
    }

    private function persons(): PersonRepository
    {
        return self::getContainer()->get(PersonRepository::class);
    }
}

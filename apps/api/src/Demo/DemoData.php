<?php

namespace App\Demo;

use App\Shared\Identities;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * The data of the demonstration and of the API tests, made as the use cases make them (ADR 0048): through
 * the domain and the repositories, each aggregate then publishing what happened to it, so that the other
 * contexts react, the journal keeps it and the search finds it. The account SOLEAN IT, its prospector and its
 * assistant (who sign in with DemoCredentials), and Babbage & Co with its contact Ada
 * Lovelace. The identities are fixed: the tests name them.
 */
final readonly class DemoData
{
    public const string SOLEAN_IT = '01900000-0000-7000-8000-0000000000a1';
    public const string PROSPECTOR = '01900000-0000-7000-8000-000000000001';
    /** A prospector without the right to manage personal data: sees the contacts masked. */
    public const string ASSISTANT = '01900000-0000-7000-8000-000000000002';
    private const string OPENED_AT = '2026-10-13 09:00';

    public function __construct(
        private AccountRepository $accounts,
        private MemberRepository $members,
        private PersonRepository $persons,
        private OrganizationRepository $organizations,
        private Identities $identities,
        private Events $events,
    ) {
    }

    public function load(): void
    {
        $soleanIt = Account::open(new AccountId(self::SOLEAN_IT), new LegalIdentity('SOLEAN IT', 'SARL'), new \DateTimeImmutable(self::OPENED_AT), new ContactDetails(new Email('org@solean-it.io'), language: 'FR'));
        $prospector = $this->aMember(self::PROSPECTOR, 'prospector@solean-it.io', $soleanIt, Role::User, Role::Prospector, Role::PersonalData);
        $this->aMember(self::ASSISTANT, 'assistant@solean-it.io', $soleanIt, Role::User, Role::Prospector);
        $soleanIt->relocateTo(new Address('1 place Kléber', '67000', 'Strasbourg', 'FR'), new GeoPoint(7.7456, 48.5834), $prospector, new \DateTimeImmutable(self::OPENED_AT));
        $this->accounts->save($soleanIt);
        $this->publish($soleanIt);
        $this->aContact($soleanIt);
    }

    private function aMember(string $id, string $email, Account $account, Role ...$roles): Member
    {
        $profile = Person::collect(new PersonId($this->identities->next()), $account->id(), Purpose::Membership, new \DateTimeImmutable(self::OPENED_AT), contactDetails: new ContactDetails(new Email($email), language: 'FR'));
        $this->persons->save($profile);
        $this->publish($profile);
        $member = Member::register(new MemberId($id), $account->id(), $profile->id(), ...$roles);
        $this->members->save($member);

        return $member;
    }

    /**
     * An organization SOLEAN IT prospects, Babbage & Co, and its contact: a contact never comes alone (ADR 0042),
     * Prospecting attaches it when PersonalData tells it was collected.
     */
    private function aContact(Account $account): void
    {
        $openedAt = new \DateTimeImmutable(self::OPENED_AT);
        $prospector = new Prospector(new MemberId(self::PROSPECTOR), $account->id(), managesPersonalData: true);
        $babbage = Organization::prospectedBy($prospector, new OrganizationId($this->identities->next()), new LegalIdentity('Babbage & Co', 'SAS'), new ContactDetails(new Email('contact@babbage.test')), $openedAt);
        $babbage->relocateTo(new Address('2 rue des Clefs', '68000', 'Colmar', 'FR'), new GeoPoint(7.3585, 48.0794), $prospector, $openedAt);
        $manager = new Reader($prospector->id, $account->id(), new PersonId($this->identities->next()), true);
        $ada = Person::collectAsContact($manager, new PersonId($this->identities->next()), new PersonName('Ada', 'Lovelace'), 'Directrice commerciale', new ContactDetails(new Email('ada@babbage.test'), PhoneNumber::of('03 88 00 00 42'), 'FR'), [$babbage->id()], $openedAt);
        $this->organizations->save($babbage);
        $this->publish($babbage);
        $this->persons->save($ada);
        $this->publish($ada);
    }

    /**
     * What happened to the aggregate, published as a use case publishes it.
     */
    private function publish(ReleasesEvents $aggregate): void
    {
        $this->events->publish(...$aggregate->releaseEvents());
    }
}

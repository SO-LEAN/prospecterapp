<?php

declare(strict_types=1);

namespace App\Demo;

use App\Shared\Identities;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\PersonalData\Domain\Model\Civility;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\NewContact;
use Prospecter\Core\PersonalData\Domain\Model\OrganizationId as PersonalDataOrganizationId;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Location;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;
use Symfony\Component\Clock\ClockInterface;

/**
 * The data of the demonstration and of the API tests, made as the use cases make them (ADR 0048). It goes through the
 * domain and the repositories. Each aggregate then publishes what happened to it, so the other contexts react and the
 * search finds it. The data hold the account SOLEAN IT, its prospector Camille Martin, its assistant Louis Petit and a
 * newcomer whose profile is empty. They sign in with DemoCredentials. The data also hold Babbage & Co, its contact Ada
 * Lovelace and two exchanges with her. SOLEAN IT connects to Google through its double's client. The ids are fixed,
 * because the tests name them.
 */
final readonly class DemoData
{
    public const string SOLEAN_IT = '01900000-0000-7000-8000-0000000000a1';
    public const string PROSPECTOR = '01900000-0000-7000-8000-000000000001';
    /** A prospector without the right to manage personal data, who sees the contacts masked. */
    public const string ASSISTANT = '01900000-0000-7000-8000-000000000002';
    /** A prospector who signs in for the first time. Their profile is empty, and they complete it. */
    public const string NEWCOMER = '01900000-0000-7000-8000-000000000003';
    private const string OPENED_AT = '2026-10-13 09:00';
    /** The INSEE code of Colmar, the municipality where Babbage & Co is (ADR 0142). */
    private const string COLMAR = '68066';
    /** The Google double's client (docker/nginx/conf.d/google-double.conf), which grants every code (ADR 0125). */
    private const string CLIENT_OF_THE_DOUBLE = 'client-of-the-double';
    private const string SECRET_OF_THE_DOUBLE = 'secret-of-the-double';

    public function __construct(
        private AccountRepository $accounts,
        private MemberRepository $members,
        private PersonRepository $persons,
        private OrganizationRepository $organizations,
        private ExchangeRepository $exchanges,
        private ContactToAttachRepository $contactsToAttach,
        private ProviderClientRepository $clients,
        private Identities $identities,
        private Events $events,
        private ClockInterface $clock,
    ) {
    }

    public function load(): void
    {
        $soleanIt = Account::open(new AccountId(self::SOLEAN_IT), new LegalIdentity('SOLEAN IT', 'SARL'), new \DateTimeImmutable(self::OPENED_AT), new ContactDetails(new Email('org@solean-it.io'), language: 'FR'));
        $prospector = $this->aMember(self::PROSPECTOR, $this->aProfileIn($soleanIt, new PersonName('Camille', 'Martin'), 'prospector@solean-it.io'), Role::User, Role::Prospector, Role::PersonalData);
        $this->aMember(self::ASSISTANT, $this->aProfileIn($soleanIt, new PersonName('Louis', 'Petit'), 'assistant@solean-it.io'), Role::User, Role::Prospector);
        $this->aMember(self::NEWCOMER, $this->anEmptyProfileIn($soleanIt), Role::User, Role::Prospector);
        $soleanIt->relocateTo(new Address('1 place Kléber', '67000', 'Strasbourg', 'FR'), new GeoPoint(7.7456, 48.5834), $prospector, new \DateTimeImmutable(self::OPENED_AT));
        $this->accounts->save($soleanIt);
        $this->publish($soleanIt);
        $this->aContact($soleanIt);
        $this->theClientOfTheDoubleOfGoogle($soleanIt, $prospector);
    }

    private function theClientOfTheDoubleOfGoogle(Account $account, Member $configuredBy): void
    {
        $credentials = new ClientCredentials(Provider::Google, self::CLIENT_OF_THE_DOUBLE, self::SECRET_OF_THE_DOUBLE);
        $client = ProviderClient::configure($account->id(), $credentials, $configuredBy->id(), new \DateTimeImmutable(self::OPENED_AT));
        $this->clients->save($client);
        $this->publish($client);
    }

    private function aProfileIn(Account $account, PersonName $name, string $email): Person
    {
        return $this->collected(Person::collect(new Gesture($account->id(), new MemberId(self::PROSPECTOR), new \DateTimeImmutable(self::OPENED_AT)), new PersonId($this->identities->next()), Purpose::Membership, new ContactCard($name, new ContactDetails(new Email($email), language: 'FR'))));
    }

    private function anEmptyProfileIn(Account $account): Person
    {
        return $this->collected(Person::collect(new Gesture($account->id(), new MemberId(self::PROSPECTOR), new \DateTimeImmutable(self::OPENED_AT)), new PersonId($this->identities->next()), Purpose::Membership));
    }

    private function collected(Person $profile): Person
    {
        $this->persons->save($profile);
        $this->publish($profile);

        return $profile;
    }

    private function aMember(string $id, Person $profile, Role ...$roles): Member
    {
        $member = Member::register(new MemberId($id), $profile->accountId(), new ProfileId($profile->id()->value), ...$roles);
        $this->members->save($member);

        return $member;
    }

    /**
     * An organization that SOLEAN IT prospects, Babbage & Co, and its contact. A contact never comes alone (ADR 0042):
     * Prospecting attaches it when PersonalData tells that it was collected.
     */
    private function aContact(Account $account): void
    {
        $openedAt = new \DateTimeImmutable(self::OPENED_AT);
        $prospector = new Prospector(new MemberId(self::PROSPECTOR), $account->id(), managesPersonalData: true);
        $babbage = Organization::prospectedBy(new Gesture($prospector->accountId, $prospector->id, $openedAt), new OrganizationId($this->identities->next()), new LegalIdentity('Babbage & Co', 'SAS'), new ContactDetails(new Email('contact@babbage.test')));
        $babbage->relocateTo(new Address('2 rue des Clefs', '68000', 'Colmar', 'FR'), new Location(new GeoPoint(7.3585, 48.0794), self::COLMAR), $prospector->id, $openedAt);
        $manager = new Reader($prospector->id, $account->id(), new PersonId($this->identities->next()), true);
        $ada = Person::collectAsContact($manager, new NewContact(new PersonId($this->identities->next()), new ContactCard(new PersonName('Ada', 'Lovelace'), new ContactDetails(new Email('ada@babbage.test'), PhoneNumber::of('03 88 00 00 42'), 'FR'), Civility::Ms, 'Directrice commerciale', ExternalReferences::designating(['https://www.linkedin.com/in/ada-lovelace'], Designated::Person)), [new PersonalDataOrganizationId($babbage->id()->value)]), $openedAt);
        $this->organizations->save($babbage);
        $this->publish($babbage);
        $this->persons->save($ada);
        $this->publish($ada);
        $this->anExchange($prospector, $ada, $babbage, new ExchangeFacts(Channel::Call, Direction::Outbound, $openedAt->modify('+1 hour')));
        $this->anExchange($prospector, $ada, $babbage, new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, $openedAt->modify('+1 day'), 'Intéressée par une mission en mars'));
    }

    /**
     * A person who took a video call with the prospector two days from now, before anyone knew who she works for. She
     * is a contact to attach, and the prospector holds the meeting (ADR 0146). Her email is on Babbage & Co's domain.
     * She exists on the stack only (`make db`): the tests start from the data of `load()`, which their expectations
     * count.
     */
    public function addAContactToAttach(): void
    {
        $account = $this->accounts->get(new AccountId(self::SOLEAN_IT));
        $now = $this->clock->now();
        $meeting = $now->setTimezone(new \DateTimeZone('Europe/Paris'))->modify('+2 days')->setTime(10, 0);
        $prospector = new Prospector(new MemberId(self::PROSPECTOR), $account->id(), managesPersonalData: true);
        $manager = new Reader($prospector->id, $account->id(), new PersonId($this->identities->next()), true);
        $grace = Person::collectToAttach($manager, new PersonId($this->identities->next()), new ContactCard(new PersonName('Grace', 'Hopper'), new ContactDetails(new Email('grace@babbage.test'), language: 'FR')), $now);
        $this->persons->save($grace);
        $this->publish($grace);
        $toAttach = ContactToAttach::metBy($prospector, new ContactId($grace->id()->value), $meeting, $now);
        $this->contactsToAttach->save($toAttach);
        $this->publish($toAttach);
        $visio = Exchange::planned(new Gesture($account->id(), $prospector->id, $now), new ExchangeId($this->identities->next()), new ContactCorrespondent(new ContactId($grace->id()->value), []), ExchangeFacts::planned(Channel::VideoCall, $meeting, Duration::inMinutes(null), 'Découverte'));
        $this->exchanges->save($visio);
        $this->publish($visio);
    }

    private function anExchange(Prospector $prospector, Person $contact, Organization $organization, ExchangeFacts $facts): void
    {
        $exchange = Exchange::noted(new Gesture($prospector->accountId, $prospector->id, $facts->occurredAt), new ExchangeId($this->identities->next()), new ContactCorrespondent(new ContactId($contact->id()->value), [$organization->id()]), Noting::of($facts, ExchangeStatus::Done));
        $this->exchanges->save($exchange);
        $this->publish($exchange);
    }

    /**
     * Publishes what happened to the aggregate, as a use case does.
     */
    private function publish(ReleasesEvents $aggregate): void
    {
        $this->events->publish(...$aggregate->releaseEvents());
    }
}

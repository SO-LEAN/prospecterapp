<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The contacts waiting for the member who met them to attach them to an organization (ADR 0146): on their dashboard
 * with the day they are erased, on their page, the organization of the domain of their email proposed, met again by
 * their holder only. The clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class ContactsToAttachApiTest extends ApiTestCase
{
    private const string TO_PROCESS = '/api/me/exchanges-to-process';

    private string $grace;
    private string $bob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grace = $this->aContactToAttachOf('prospector-user', 'Grace', 'Hopper', 'grace@acme.test', '2026-10-20 14:00 Europe/Paris');
        $this->bob = $this->aContactToAttachOf('prospector-user', 'Bob', 'Martin', 'bob.martin@gmail.com', '2026-10-18 10:00 Europe/Paris');
    }

    public function testTheContactsIHoldWaitingToBeAttachedAreToProcessTheFirstErasedFirst(): void
    {
        $this->login();

        $toAttach = $this->mine($this->requestJson('GET', self::TO_PROCESS)['contactsToAttach']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$this->bob, $this->grace], array_column($toAttach, 'id'));
        $this->assertSame([
            'id' => $this->grace,
            'name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'],
            'email' => 'grace@acme.test',
            'lastMeetingAt' => '2026-10-20T12:00:00+00:00',
            'erasableFrom' => '2026-11-18T23:00:00+00:00',
            '_links' => [
                'self' => ['href' => '/api/contacts/'.$this->grace, 'method' => 'GET'],
                'proposedOrganization' => ['href' => '/api/contacts/'.$this->grace.'/proposed-organization', 'method' => 'GET'],
            ],
        ], $toAttach[1]);
    }

    public function testTheContactsToAttachOfAColleagueAreNotMine(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Prospector, Role::PersonalData, Role::Admin));

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['contactsToAttach']);
    }

    public function testTheOrganizationOfTheDomainOfTheirEmailIsProposed(): void
    {
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => 'contact@acme.test']])['id'];

        $this->assertSame(['organization' => ['id' => $acme, 'name' => 'ACME']], $this->requestJson('GET', '/api/contacts/'.$this->grace.'/proposed-organization'));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAnOrganizationWhoseEmailChangedIsProposedByItsNewDomainOnly(): void
    {
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => 'contact@acme.test']])['id'];

        $this->requestJson('PATCH', '/api/organizations/'.$acme.'/contact-details', ['email' => 'hello@acme-group.test', 'phoneNumber' => null, 'language' => null]);

        $this->assertSame(['organization' => null], $this->requestJson('GET', '/api/contacts/'.$this->grace.'/proposed-organization'));
    }

    public function testAPublicMailboxProposesNoOrganization(): void
    {
        $this->login();
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Les amis de Gmail'], 'contactDetails' => ['email' => 'amis@gmail.com']]);

        $this->assertSame(['organization' => null], $this->requestJson('GET', '/api/contacts/'.$this->bob.'/proposed-organization'));
    }

    public function testTheProposalNeedsTheRightToManagePersonalData(): void
    {
        $this->login('assistant-user');

        $this->requestJson('GET', '/api/contacts/'.$this->grace.'/proposed-organization');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testThePageOfAContactToAttachSaysWhenTheyAreErasedToTheirHolderOnly(): void
    {
        $this->login();
        $this->assertSame(['lastMeetingAt' => '2026-10-20T12:00:00+00:00', 'erasableFrom' => '2026-11-18T23:00:00+00:00'], $this->requestJson('GET', '/api/contacts/'.$this->grace)['toAttach']);

        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Prospector, Role::PersonalData, Role::Admin));
        $this->assertArrayNotHasKey('toAttach', $this->requestJson('GET', '/api/contacts/'.$this->grace));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testTheirHolderPlansAndNotesAToDoWithThem(): void
    {
        $this->login();

        $this->requestJson('POST', '/api/contacts/'.$this->grace.'/upcoming-exchanges', ['channel' => 'call', 'occurredAt' => '2026-10-25T09:00:00+00:00']);
        $this->assertResponseStatusCodeSame(201);
        $this->requestJson('POST', '/api/contacts/'.$this->grace.'/to-dos', ['text' => 'Envoyer la proposition', 'dueOn' => '2026-10-16']);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testAColleagueDoesNotPlanWithThem(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Prospector, Role::PersonalData, Role::Admin));

        $refused = $this->requestJson('POST', '/api/contacts/'.$this->grace.'/upcoming-exchanges', ['channel' => 'call', 'occurredAt' => '2026-10-25T09:00:00+00:00']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('ContactToAttachOfAnother', $refused['reason']);
    }

    public function testAttachedTheyAreNoLongerToAttachTheirMeetingKeptForTheOrganization(): void
    {
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        $this->requestJson('POST', '/api/organizations/'.$acme.'/contacts', ['personId' => $this->grace]);

        $this->assertSame([$this->bob], array_column($this->mine($this->requestJson('GET', self::TO_PROCESS)['contactsToAttach']), 'id'));
        $this->assertSame([[$acme]], array_column(array_column($this->requestJson('GET', '/api/contacts/'.$this->grace.'/upcoming-exchanges')['items'], 'exchange'), 'organizationIds'));
    }

    /**
     * Those the test arranged, among those of the demonstration.
     *
     * @param list<array<string, mixed>> $contactsToAttach
     *
     * @return list<array<string, mixed>>
     */
    private function mine(array $contactsToAttach): array
    {
        return array_values(array_filter($contactsToAttach, fn (array $contact): bool => in_array($contact['id'], [$this->grace, $this->bob], true)));
    }

    /**
     * Arranged as the booking of a meeting will: the person collected without organization, met by the member, the
     * meeting planned and held by them.
     */
    private function aContactToAttachOf(string $holder, string $firstName, string $lastName, string $email, string $meeting): string
    {
        $member = new MemberId($this->memberIdOf($holder));
        $at = new \DateTimeImmutable('2026-10-15 09:00 UTC');
        $person = Person::collectToAttach(new Reader($member, $this->accountOfTheFixtures(), new PersonId(self::newIdentity()), true), new PersonId(self::newIdentity()), new PersonName($firstName, $lastName), new ContactDetails(new Email($email)), $at);
        self::getContainer()->get(PersonRepository::class)->save($person);
        $prospector = new Prospector($member, $this->accountOfTheFixtures(), managesPersonalData: true);
        self::getContainer()->get(ContactToAttachRepository::class)->save(ContactToAttach::metBy($prospector, $person->id(), new \DateTimeImmutable($meeting), $at));
        $facts = ExchangeFacts::planned(Channel::VideoCall, new \DateTimeImmutable($meeting), Duration::inMinutes(null), null);
        self::getContainer()->get(ExchangeRepository::class)->save(Exchange::plannedWithAContact($this->accountOfTheFixtures(), $member, new ExchangeId(self::newIdentity()), $person->id(), [], $facts, $at));

        return $person->id()->value;
    }
}

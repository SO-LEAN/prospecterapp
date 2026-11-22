<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Demo\DemoData;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
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
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * The contacts waiting for the member who met them to attach them to an organization (ADR 0146): on their dashboard
 * with the day they are erased, on their page, the organization of the domain of their email proposed, met again by
 * their holder only. The clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class ContactsToAttachApiTest extends ApiTestCase
{
    use ReadsResources;

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

        $toAttach = $this->mine(self::recordsAt($this->requestJson('GET', self::TO_PROCESS), 'contactsToAttach'));

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

    public function testWithoutTheRightTheirHolderGetsNoProposal(): void
    {
        $louise = $this->aContactToAttachOf('assistant-user', 'Louise', 'Michel', 'louise@acme.test', '2026-10-20 14:00 Europe/Paris');
        $this->login('assistant-user');

        $toAttach = self::recordsAt($this->requestJson('GET', self::TO_PROCESS), 'contactsToAttach');

        $this->assertSame([$louise], array_column($toAttach, 'id'));
        $this->assertSame(['self'], array_keys(self::recordAt($toAttach, 0, '_links')));
        $this->assertArrayNotHasKey('proposedOrganization', self::recordAt($this->requestJson('GET', '/api/contacts/'.$louise), '_links'));
    }

    public function testTheirHolderWithoutTheRightPlansWithThemButNotesNothing(): void
    {
        $louise = $this->aContactToAttachOf('assistant-user', 'Louise', 'Michel', 'louise@acme.test', '2026-10-20 14:00 Europe/Paris');
        $this->login('assistant-user');

        $this->requestJson('POST', '/api/contacts/'.$louise.'/upcoming-exchanges', ['channel' => 'call', 'occurredAt' => '2026-10-25T09:00:00+00:00']);
        $this->assertResponseStatusCodeSame(201);
        $this->requestJson('POST', '/api/contacts/'.$louise.'/exchanges', ['channel' => 'call', 'direction' => 'outbound', 'occurredAt' => '2026-10-14T09:00:00+00:00']);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('POST', '/api/contacts/'.$louise.'/to-dos', ['text' => 'Envoyer la proposition', 'dueOn' => '2026-10-16']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testThePageOfAContactToAttachLinksTheProposalToTheirHolderWithTheRightOnly(): void
    {
        $this->login();
        $this->assertSame(['href' => '/api/contacts/'.$this->grace.'/proposed-organization', 'method' => 'GET'], self::valueAt($this->requestJson('GET', '/api/contacts/'.$this->grace), '_links', 'proposedOrganization'));

        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Prospector, Role::PersonalData, Role::Admin));
        $this->assertArrayNotHasKey('proposedOrganization', self::recordAt($this->requestJson('GET', '/api/contacts/'.$this->grace), '_links'));
    }

    public function testTheirExchangesSayTheyAreWithAContactToAttach(): void
    {
        $this->login();

        $this->assertSame([true], array_column(self::recordsAt($this->requestJson('GET', '/api/contacts/'.$this->grace.'/upcoming-exchanges'), 'items'), 'contactToAttach'));
    }

    public function testTheContactsToAttachOfAColleagueAreNotMine(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Prospector, Role::PersonalData, Role::Admin));

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['contactsToAttach']);
    }

    public function testTheOrganizationOfTheDomainOfTheirEmailIsProposed(): void
    {
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => 'contact@acme.test']]), 'id');

        $this->assertSame(['organization' => ['id' => $acme, 'name' => 'ACME']], $this->requestJson('GET', '/api/contacts/'.$this->grace.'/proposed-organization'));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAnOrganizationWhoseEmailChangedIsProposedByItsNewDomainOnly(): void
    {
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => 'contact@acme.test']]), 'id');

        $this->requestJson('PATCH', '/api/organizations/'.$acme.'/contact-details', ['email' => 'hello@acme-group.test', 'phoneNumber' => null, 'language' => null]);

        $this->assertSame(['organization' => null], $this->requestJson('GET', '/api/contacts/'.$this->grace.'/proposed-organization'));
    }

    public function testAnOrganizationWhoseEmailChangedWithinItsDomainIsStillProposed(): void
    {
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => 'contact@acme.test']]), 'id');

        $this->requestJson('PATCH', '/api/organizations/'.$acme.'/contact-details', ['email' => 'hello@acme.test', 'phoneNumber' => null, 'language' => null]);

        $this->assertSame(['organization' => ['id' => $acme, 'name' => 'ACME']], $this->requestJson('GET', '/api/contacts/'.$this->grace.'/proposed-organization'));
    }

    public function testTheKeysRebuiltWriteTheDomainOfTheEmailOfAnOrganizationAgain(): void
    {
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => 'contact@acme.test']]), 'id');
        self::tableOf('prospecting')->batch([], [['EMAIL_DOMAIN#'.DemoData::SOLEAN_IT.'#acme.test', 'ORGANIZATION#'.$acme]]);

        new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:keys:rebuild'))->execute([]);

        $this->assertSame(['organization' => ['id' => $acme, 'name' => 'ACME']], $this->requestJson('GET', '/api/contacts/'.$this->grace.'/proposed-organization'));
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

    public function testAColleagueReadsTheirTimelineButNotesNorPlansNothing(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Prospector, Role::PersonalData, Role::Admin));

        $links = self::recordAt($this->requestJson('GET', '/api/contacts/'.$this->grace.'/exchanges'), '_links');
        $this->assertSame([false, false], [isset($links['noteExchange']), isset($links['planExchange'])]);
        $noted = $this->requestJson('POST', '/api/contacts/'.$this->grace.'/exchanges', ['channel' => 'call', 'direction' => 'outbound', 'occurredAt' => '2026-10-14T09:00:00+00:00']);
        $this->assertSame([403, 'contactToAttachOfAnother'], [$this->client->getResponse()->getStatusCode(), $noted['reason']]);
        $toDo = $this->requestJson('POST', '/api/contacts/'.$this->grace.'/to-dos', ['text' => 'Envoyer la proposition', 'dueOn' => '2026-10-16']);
        $this->assertSame([403, 'contactToAttachOfAnother'], [$this->client->getResponse()->getStatusCode(), $toDo['reason']]);
    }

    public function testAColleagueDoesNotPlanWithThem(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Prospector, Role::PersonalData, Role::Admin));

        $refused = $this->requestJson('POST', '/api/contacts/'.$this->grace.'/upcoming-exchanges', ['channel' => 'call', 'occurredAt' => '2026-10-25T09:00:00+00:00']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('contactToAttachOfAnother', $refused['reason']);
    }

    public function testAttachedTheyAreNoLongerToAttachTheirMeetingKeptForTheOrganization(): void
    {
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']]), 'id');

        $this->requestJson('POST', '/api/organizations/'.$acme.'/contacts', ['personId' => $this->grace]);

        $this->assertSame([$this->bob], array_column($this->mine(self::recordsAt($this->requestJson('GET', self::TO_PROCESS), 'contactsToAttach')), 'id'));
        $upcoming = self::recordsAt($this->requestJson('GET', '/api/contacts/'.$this->grace.'/upcoming-exchanges'), 'items');
        $this->assertSame([[[$acme], false]], array_map(static fn (array $exchange): array => [$exchange['organizationIds'], $exchange['contactToAttach']], $upcoming));
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
        $person = Person::collectToAttach(new Reader($member, $this->accountOfTheFixtures(), new PersonId(self::newIdentity()), true), new PersonId(self::newIdentity()), new ContactCard(new PersonName($firstName, $lastName), new ContactDetails(new Email($email))), $at);
        self::service(PersonRepository::class)->save($person);
        $prospector = new Prospector($member, $this->accountOfTheFixtures(), managesPersonalData: true);
        self::service(ContactToAttachRepository::class)->save(ContactToAttach::metBy($prospector, $person->id(), new \DateTimeImmutable($meeting), $at));
        $facts = ExchangeFacts::planned(Channel::VideoCall, new \DateTimeImmutable($meeting), Duration::inMinutes(null), null);
        self::service(ExchangeRepository::class)->save(Exchange::planned(new Gesture($this->accountOfTheFixtures(), $member, $at), new ExchangeId(self::newIdentity()), new ContactCorrespondent($person->id(), []), $facts));

        return $person->id()->value;
    }
}

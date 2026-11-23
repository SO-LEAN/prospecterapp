<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Task;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * The to-dos for a contact or an organization (ADR 0113): the clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class ToDoApiTest extends ApiTestCase
{
    use ReadsResources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAToDoNotedForAContactIsOnItsCardAndOnThoseOfItsOrganizations(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');

        $toDo = $this->requestJson('POST', '/api/contacts/'.$ada.'/to-dos', ['text' => 'Envoyer mon CV', 'dueOn' => '2026-10-16', 'dueAt' => '08:30']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/to-dos/'.self::stringAt($toDo, 'id'));
        $this->assertSame(['Envoyer mon CV', '2026-10-16', '08:30', null, ['firstName' => 'Ada', 'lastName' => 'Lovelace']], [$toDo['text'], $toDo['dueOn'], $toDo['dueAt'], $toDo['doneAt'], self::valueAt($toDo, 'contact', 'name')]);
        $heldBy = self::recordAt($toDo, 'heldBy');
        $this->assertNotNull($heldBy['email'] ?? self::valueAt($heldBy, 'name', 'firstName'));
        $this->assertSame(['self', 'revise', 'tickOff', 'erase'], array_keys(self::arrayAt($toDo, '_links')));
        $ofTheContact = $this->requestJson('GET', '/api/contacts/'.$ada.'/to-dos');
        $this->assertSame([$toDo], $ofTheContact['toDos']);
        $this->assertSame(['href' => '/api/contacts/'.$ada.'/to-dos', 'method' => 'POST'], self::valueAt($ofTheContact, '_links', 'noteToDo'));
        $this->assertSame([$toDo], self::valueAt($this->requestJson('GET', '/api/organizations/'.$organization.'/to-dos'), 'toDos'));
        $this->assertSame([$toDo], self::valueAt($this->requestJson('GET', '/api/me/to-dos'), 'toDos'));
    }

    public function testTheCardsLeadToTheirToDos(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');

        $this->assertSame(['href' => '/api/contacts/'.$ada.'/to-dos', 'method' => 'GET'], self::valueAt($this->requestJson('GET', '/api/contacts/'.$ada), '_links', 'toDos'));
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/to-dos', 'method' => 'GET'], self::valueAt($this->requestJson('GET', '/api/organizations/'.$organization), '_links', 'toDos'));
    }

    public function testAToDoIsTickedOffThenUnticked(): void
    {
        $organization = $this->anOrganization('ACME');
        $toDo = $this->requestJson('POST', '/api/organizations/'.$organization.'/to-dos', ['text' => 'Préparer la proposition', 'dueOn' => '2026-10-20']);
        $this->assertResponseStatusCodeSame(201);
        $this->assertNull($toDo['contact']);

        $done = $this->requestJson('POST', '/api/to-dos/'.self::stringAt($toDo, 'id').'/done');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['self', 'revise', 'untick', 'erase'], array_keys(self::arrayAt($done, '_links')));
        $this->assertNotNull($done['doneAt']);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/to-dos'), 'toDos'));

        $this->requestJson('POST', '/api/to-dos/'.self::stringAt($toDo, 'id').'/done');
        $this->assertResponseStatusCodeSame(409);

        $this->assertNull(self::valueAt($this->requestJson('DELETE', '/api/to-dos/'.self::stringAt($toDo, 'id').'/done'), 'doneAt'));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAToDoIsRevisedThenErased(): void
    {
        $organization = $this->anOrganization('ACME');
        $toDo = $this->requestJson('POST', '/api/organizations/'.$organization.'/to-dos', ['text' => 'Préparer la proposition', 'dueOn' => '2026-10-20']);

        $revised = $this->requestJson('PATCH', '/api/to-dos/'.self::stringAt($toDo, 'id'), ['text' => 'Préparer la proposition chiffrée', 'dueOn' => '2026-10-21', 'dueAt' => '09:00']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['Préparer la proposition chiffrée', '2026-10-21', '09:00'], [$revised['text'], $revised['dueOn'], $revised['dueAt']]);
        $this->assertSame($revised, $this->requestJson('GET', '/api/to-dos/'.self::stringAt($toDo, 'id')));
        $this->assertResponseStatusCodeSame(200);

        $this->client->request('DELETE', '/api/to-dos/'.self::stringAt($toDo, 'id'));
        $this->assertResponseStatusCodeSame(204);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/organizations/'.$organization.'/to-dos'), 'toDos'));
        $this->requestJson('GET', '/api/to-dos/'.self::stringAt($toDo, 'id'));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheRulesOfAToDoAreThoseOfTheCore(): void
    {
        $ada = $this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace');

        $withoutText = $this->requestJson('POST', '/api/contacts/'.$ada.'/to-dos', ['text' => ' ', 'dueOn' => '2026-10-16']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['text'], 'toDoWithoutText'], [$this->violatedFields($withoutText), $withoutText['reason']]);
        $this->requestJson('POST', '/api/contacts/'.$ada.'/to-dos', ['text' => 'Envoyer mon CV', 'dueOn' => '16/10/2026', 'dueAt' => '8h30']);
        $this->assertResponseStatusCodeSame(422);
        $this->requestJson('POST', '/api/contacts/'.self::newIdentity().'/to-dos', ['text' => 'Envoyer mon CV', 'dueOn' => '2026-10-16']);
        $this->assertResponseStatusCodeSame(404);
        $unknown = $this->requestJson('GET', '/api/contacts/'.self::newIdentity().'/to-dos');
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownContact', $unknown['reason']);
    }

    public function testStoppingToProspectTheOrganizationTakesItsToDosAlong(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->requestJson('POST', '/api/organizations/'.$organization.'/to-dos', ['text' => 'Préparer la proposition', 'dueOn' => '2026-10-20']);

        $this->requestJson('DELETE', '/api/organizations/'.$organization);

        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/to-dos'), 'toDos'));
    }

    public function testErasingAContactErasesItsToDos(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->requestJson('POST', '/api/contacts/'.$ada.'/to-dos', ['text' => 'Envoyer mon CV', 'dueOn' => '2026-10-16']);

        $this->requestJson('DELETE', '/api/contacts/'.$ada);

        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/organizations/'.$organization.'/to-dos'), 'toDos'));
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/to-dos'), 'toDos'));
    }

    public function testAColleagueReadsMyToDosOnTheCardsButNotAmongTheirs(): void
    {
        $organization = $this->anOrganization('ACME');
        $toDo = $this->requestJson('POST', '/api/organizations/'.$organization.'/to-dos', ['text' => 'Préparer la proposition', 'dueOn' => '2026-10-20']);
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->assertSame([$toDo['id']], array_column(self::recordsAt($this->requestJson('GET', '/api/organizations/'.$organization.'/to-dos'), 'toDos'), 'id'));
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/to-dos'), 'toDos'));
        $this->requestJson('POST', '/api/to-dos/'.self::stringAt($toDo, 'id').'/done');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testWithoutTheRightToManagePersonalDataNoToDoIsReadNorNoted(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $toDo = $this->requestJson('POST', '/api/organizations/'.$organization.'/to-dos', ['text' => 'Préparer la proposition', 'dueOn' => '2026-10-20']);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertArrayNotHasKey('toDos', self::arrayAt($this->requestJson('GET', '/api/contacts/'.$ada), '_links'));
        $this->assertArrayNotHasKey('toDos', self::arrayAt($this->requestJson('GET', '/api/organizations/'.$organization), '_links'));
        $gestures = [
            ['GET', '/api/contacts/'.$ada.'/to-dos', null], ['POST', '/api/contacts/'.$ada.'/to-dos', ['text' => 'Appeler', 'dueOn' => '2026-10-16']],
            ['GET', '/api/organizations/'.$organization.'/to-dos', null], ['POST', '/api/organizations/'.$organization.'/to-dos', ['text' => 'Appeler', 'dueOn' => '2026-10-16']],
            ['GET', '/api/me/to-dos', null], ['GET', '/api/to-dos/'.self::stringAt($toDo, 'id'), null],
            ['PATCH', '/api/to-dos/'.self::stringAt($toDo, 'id'), ['text' => 'Appeler', 'dueOn' => '2026-10-16']],
            ['POST', '/api/to-dos/'.self::stringAt($toDo, 'id').'/done', null], ['DELETE', '/api/to-dos/'.self::stringAt($toDo, 'id').'/done', null], ['DELETE', '/api/to-dos/'.self::stringAt($toDo, 'id'), null],
        ];

        foreach ($gestures as [$method, $path, $payload]) {
            $this->requestJson($method, $path, $payload);
            $this->assertResponseStatusCodeSame(403, $method.' '.$path);
        }
    }

    public function testAToDoOfAnotherAccountIsNotFoundForEveryIntention(): void
    {
        $id = $this->aToDoOfAnotherAccount();
        $intentions = [['GET', '', null], ['PATCH', '', ['text' => 'Rien', 'dueOn' => '2026-10-16']], ['DELETE', '', null], ['POST', '/done', null], ['DELETE', '/done', null]];

        foreach ($intentions as [$method, $path, $payload]) {
            $problem = $this->requestJson($method, '/api/to-dos/'.$id.$path, $payload);
            $this->assertResponseStatusCodeSame(404, $method.' '.$path);
            $this->assertSame('unknownToDo', $problem['reason'], $method.' '.$path);
        }
    }

    private function aToDoOfAnotherAccount(): string
    {
        $competitor = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Competitor'), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        self::service(AccountRepository::class)->save($competitor);
        $theirs = Organization::prospectedBy(new Gesture($competitor->id(), new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new OrganizationId(self::newIdentity()), new LegalIdentity('Their prospect'), new ContactDetails());
        self::service(OrganizationRepository::class)->save($theirs);
        $toDo = ToDo::noted(new Gesture($competitor->id(), new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-10-14')), new ToDoId(self::newIdentity()), new OrganizationCorrespondent($theirs->id()), new Task('Leur proposition', new Due('2026-10-16')));
        self::service(ToDoRepository::class)->save($toDo);

        return $toDo->id()->value;
    }

    private function aContactOf(string $organization, string $firstName, string $lastName): string
    {
        return self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'organizationIds' => [$organization]]), 'id');
    }

    private function anOrganization(string $name): string
    {
        return self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]]), 'id');
    }
}

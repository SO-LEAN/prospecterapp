<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
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
        $this->assertSame(['name' => ['firstName' => 'Camille', 'lastName' => 'Martin'], 'email' => 'prospector@solean-it.io'], self::recordAt($toDo, 'heldBy'));
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

    public function testWithoutTheRightToManagePersonalDataTheCardsLeadToNoToDo(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertArrayNotHasKey('toDos', self::arrayAt($this->requestJson('GET', '/api/contacts/'.$ada), '_links'));
        $this->assertArrayNotHasKey('toDos', self::arrayAt($this->requestJson('GET', '/api/organizations/'.$organization), '_links'));
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

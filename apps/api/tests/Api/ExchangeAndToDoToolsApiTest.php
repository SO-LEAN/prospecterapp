<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;

/**
 * An AI plans, handles and corrects the exchanges, and notes, ticks off and erases the to-dos, as its member does on
 * the screens (ADR 0163).
 */
#[Group('api')]
class ExchangeAndToDoToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    private const array FRIDAY = ['text' => 'Envoyer le CV', 'dueOn' => '2026-10-16', 'dueAt' => '10:00'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiPlansAVideoCallWithAContact(): void
    {
        $ada = $this->ada();
        $tomorrow = ['channel' => 'videoCall', 'occurredAt' => '2026-10-16T10:00:00+02:00', 'durationInMinutes' => 30, 'text' => 'Démonstration'];

        $upcoming = $this->assertTheToolActsAsItsEndpoint('planifier-echange', ['id' => $ada, ...$tomorrow], 'POST', '/api/contacts/'.$ada.'/upcoming-exchanges', $tomorrow, self::PROSPECTORS);

        $this->assertSame([['videoCall', '2026-10-16T08:00:00+00:00', 'planned']], array_map(static fn (array $exchange): array => [$exchange['channel'], $exchange['occurredAt'], $exchange['status']], array_slice($upcoming['items'], 0, 1)));
    }

    public function testTheAiNotesAToDoForAContact(): void
    {
        $ada = $this->ada();

        $noted = $this->assertTheToolActsAsItsEndpoint('noter-a-faire', ['id' => $ada, ...self::FRIDAY], 'POST', '/api/contacts/'.$ada.'/to-dos', self::FRIDAY, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame(['Envoyer le CV', '2026-10-16', $ada], [$noted['text'], $noted['dueOn'], $noted['contact']['id']]);
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/contacts?q=Lovelace')['items'][0]['id'];
    }
}

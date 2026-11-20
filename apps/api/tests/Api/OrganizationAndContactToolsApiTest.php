<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An AI manages the organizations and the contacts as its member does on the screens (ADR 0163): each tool of a write
 * does what its endpoint does, the resource it answers that the screen reads then, or refuses as its endpoint refuses,
 * to a prospector, an assistant without the right to manage the personal data, an administrator, a member who is no
 * prospector and a member of another account alike.
 */
#[Group('api')]
class OrganizationAndContactToolsApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const array WRITERS = ['prospector-user', 'assistant-user', 'admin-user', 'member-user', 'stranger'];

    private const array PROSPECTORS = ['prospector-user', 'assistant-user', 'admin-user'];

    private const array PERSONAL_DATA_MANAGERS = ['prospector-user', 'admin-user'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiChangesTheJobTitleOfAContact(): void
    {
        $ada = $this->ada();

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-fonction-contact', ['id' => $ada, 'jobTitle' => 'CTO'], 'PATCH', '/api/contacts/'.$ada.'/job-title', ['jobTitle' => 'CTO'], self::PERSONAL_DATA_MANAGERS, '/api/contacts/'.$ada);

        $this->assertSame('CTO', $changed['jobTitle']);
    }

    public function testTheAiRenamesAContact(): void
    {
        $ada = $this->ada();
        $name = ['firstName' => 'Augusta Ada', 'lastName' => 'King'];

        $renamed = $this->assertTheToolActsAsItsEndpoint('renommer-contact', ['id' => $ada, ...$name], 'PATCH', '/api/contacts/'.$ada.'/name', $name, self::PERSONAL_DATA_MANAGERS, '/api/contacts/'.$ada);

        $this->assertSame($name, $renamed['name']);
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/contacts?q=Lovelace')['items'][0]['id'];
    }

    /**
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/organizations?q=Babbage')['items'][0]['id'];
    }

    /**
     * Each writer calls the tool: refused, the endpoint refuses them the same; done, by a writer the endpoint allows,
     * it answers the resource the screen then reads.
     *
     * @param array<string, mixed>      $arguments
     * @param array<string, mixed>|null $payload
     * @param list<string>              $allowed   the writers the endpoint allows
     *
     * @return array<string, mixed> what the tool answered the first writer it allowed
     */
    private function assertTheToolActsAsItsEndpoint(string $tool, array $arguments, string $method, string $endpoint, ?array $payload, array $allowed, string $then): array
    {
        $done = [];
        foreach (self::WRITERS as $writer) {
            $this->login($writer);
            $result = $this->callWithoutHandshake($tool, $arguments);
            $answer = $this->answerOf($result);

            if (true === ($result['isError'] ?? false)) {
                $this->assertSame($this->requestJson($method, $endpoint, $payload), $answer, sprintf('%s refused to %s', $tool, $writer));
                continue;
            }

            $this->assertContains($writer, $allowed, sprintf('%s done by %s, whom its endpoint refuses', $tool, $writer));
            $this->assertSame($this->requestJson('GET', $then), $answer, sprintf('%s done by %s', $tool, $writer));
            $done[] = $answer;
        }

        $this->assertNotSame([], $done, sprintf('%s done by nobody', $tool));

        return $done[0];
    }
}

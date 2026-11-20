<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An AI reads Prospecter as its member reads the screens (ADR 0163): each tool of reading answers what its endpoint
 * answers, the resource and its links, or its refusal, to a prospector, an assistant without the right to manage the
 * personal data, an administrator, a member who is no prospector and a member of another account alike.
 */
#[Group('api')]
class ReadingToolsApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const array READERS = ['prospector-user', 'assistant-user', 'admin-user', 'member-user', 'stranger'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiSearchesTheOrganizationsAsTheScreenOfTheSearch(): void
    {
        $this->assertTheToolAnswersAsItsEndpoint('rechercher-organisations', ['q' => 'Ba'], '/api/organizations?q=Ba');
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function assertTheToolAnswersAsItsEndpoint(string $tool, array $arguments, string $endpoint): void
    {
        foreach (self::READERS as $reader) {
            $this->login($reader);
            $answer = $this->requestJson('GET', $endpoint);

            $this->assertSame($answer, $this->answerOf($this->callWithoutHandshake($tool, $arguments)), sprintf('%s read by %s', $tool, $reader));
        }
    }

    /**
     * What the tool answered: its result, or the problem of its refusal.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function answerOf(array $result): array
    {
        return true === ($result['isError'] ?? false)
            ? json_decode($result['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR)
            : $result['structuredContent'];
    }
}

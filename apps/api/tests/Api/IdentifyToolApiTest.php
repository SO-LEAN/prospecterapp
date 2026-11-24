<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An AI asks which persons and organizations Prospecter knows before creating any (ADR 0173): each is known, new or
 * to confirm among candidates, in the order given, as its member may read them.
 */
#[Group('api')]
class IdentifyToolApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    public function testThePersonsAndTheOrganizationsAreIdentifiedInTheOrderGiven(): void
    {
        $this->login();

        $identified = $this->answerOf($this->callWithoutHandshake('identify', [
            'organizations' => [['emails' => ['contact@babbage.test']], ['name' => 'Initech']],
            'persons' => [
                ['emails' => ['ada@babbage.test']],
                ['firstName' => 'Grace', 'lastName' => 'Hopper'],
                ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'organization' => 0],
            ],
        ]));

        $this->assertSame([['known', ['Babbage & Co SAS']], ['new', []]], self::described(self::recordsAt($identified, 'organizations')));
        $this->assertSame([['known', ['Ada Lovelace']], ['new', []], ['toConfirm', ['Ada Lovelace']]], self::described(self::recordsAt($identified, 'persons')));
    }

    public function testAnAssistantWithoutTheRightToManageThePersonalDataIdentifiesAPersonByTheirNameOnly(): void
    {
        $this->login('assistant-user');

        $identified = $this->answerOf($this->callWithoutHandshake('identify', [
            'persons' => [['emails' => ['ada@babbage.test']], ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'emails' => ['ada@babbage.test']]],
        ]));

        $this->assertSame([['new', []], ['toConfirm', ['Ada Lovelace']]], self::described(self::recordsAt($identified, 'persons')));
    }

    public function testAMemberWhoIsNoProspectorIsRefused(): void
    {
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->login('member-user');

        $refused = $this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [['name' => 'Babbage']]]));

        $this->assertSame([403, 'roleNotAllowed'], [$refused['status'], $refused['reason']]);
    }

    public function testMoreThanAHundredOfAKindAreRefused(): void
    {
        $this->login();

        $this->sendWithoutHandshake('identify', ['organizations' => array_fill(0, 101, ['name' => 'Babbage'])]);

        $this->assertSame(-32602, self::valueAt($this->responseData(), 'error', 'code'));
    }

    /**
     * @param list<array<string, mixed>> $identifications
     *
     * @return list<array{string, list<string>}>
     */
    private static function described(array $identifications): array
    {
        return array_map(static fn (array $identification): array => [self::stringAt($identification, 'status'), array_map(static fn (array $candidate): string => self::stringAt($candidate, 'name'), self::recordsAt($identification, 'candidates'))], $identifications);
    }
}

<?php

namespace Tests\App\Api;

use App\Shared\Persistence\Journal\ActivityJournal;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\FailingReaction;

/**
 * A command and its reactions write all or nothing: a reaction that throws leaves the tables as they were, the
 * journal included.
 */
#[Group('api')]
class AllOrNothingApiTest extends ApiTestCase
{
    private string $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->login();
        $this->organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
    }

    public function testAReactionThatRefusesRefusesTheCommandWhichWritesNothing(): void
    {
        $this->reaction()->failWith(NotPossibleInItsState::because('Refused by the reaction'));

        $this->requestJson('PATCH', '/api/organizations/'.$this->organization.'/observations', ['observations' => 'Rappeler lundi']);

        $this->assertResponseStatusCodeSame(409);
        $this->assertTheOrganizationIsAsItWas();
    }

    public function testAReactionThatFailsFailsTheCommandWhichWritesNothing(): void
    {
        $this->reaction()->failWith(new \RuntimeException('The reaction failed'));

        $this->requestJson('PATCH', '/api/organizations/'.$this->organization.'/observations', ['observations' => 'Rappeler lundi']);

        $this->assertResponseStatusCodeSame(500);
        $this->assertTheOrganizationIsAsItWas();
    }

    private function assertTheOrganizationIsAsItWas(): void
    {
        $journal = self::getContainer()->get(ActivityJournal::class)->of(self::getContainer()->get('app.table.prospecting'), $this->organization);
        $this->assertSame(['ProspectingStarted'], array_column($journal, 'event'));
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$this->organization)['observations']);
    }

    private function reaction(): FailingReaction
    {
        return self::getContainer()->get(FailingReaction::class);
    }
}

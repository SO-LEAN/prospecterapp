<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\EmailTakenMeanwhile;
use Tests\App\Double\FailingReaction;

/**
 * A command and its reactions write all or nothing: a reaction that throws leaves the tables as they were.
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

    public function testAnEmailTakenMeanwhileIsRefusedAtTheCommitWhichWritesNothing(): void
    {
        $this->emailTakenMeanwhile()->takeTheNextEmail();
        $before = $this->requestJson('GET', '/api/me')['contactDetails'];

        $problem = $this->requestJson('PATCH', '/api/me/contact-details', ['email' => 'anais@example.com']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('emailAlreadyUsed', $problem['reason']);
        $this->assertSame($before, $this->requestJson('GET', '/api/me')['contactDetails']);
    }

    private function assertTheOrganizationIsAsItWas(): void
    {
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$this->organization)['observations']);
    }

    private function emailTakenMeanwhile(): EmailTakenMeanwhile
    {
        return self::getContainer()->get(EmailTakenMeanwhile::class);
    }

    private function reaction(): FailingReaction
    {
        return self::getContainer()->get(FailingReaction::class);
    }
}
